import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { AlertCircle, Headphones, Info, LoaderCircle, Pause, Play, RotateCcw, SkipBack, SkipForward, Volume2, VolumeX } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { getBlob, getJson } from './training-http';

export interface TrainingVoiceOption {
    key: string;
    label: string;
    locale: string;
    flag: string;
}

export interface TrainingAudioConfig {
    delivery: string;
    voices: TrainingVoiceOption[];
    voices_by_language?: Record<string, TrainingVoiceOption[]>;
    has_text_by_language?: Record<string, boolean>;
    speech_url: string;
    has_text: boolean;
}

interface SpeechPayload {
    delivery: string;
    provider: string;
    voice: TrainingVoiceOption;
    has_text: boolean;
    segments: string[];
    estimated_seconds: number;
    audio_url: string | null;
}

interface Props {
    audio: TrainingAudioConfig;
    /** Lesson language to read; each language has its own text and voices. */
    language?: string;
    /** Where the recruiter stopped last time, in seconds at 1x. */
    initialSeconds?: number;
    /** Called with the current position (seconds at 1x) while listening. */
    onPositionReport?: (seconds: number) => void;
}

type Status = 'idle' | 'loading' | 'playing' | 'paused' | 'ended' | 'error';

const SPEEDS = [0.75, 1, 1.25, 1.5, 2];
const SKIP_SECONDS = 10;
const REPORT_EVERY_MS = 15000;
const STORAGE = {
    voice: 'recruiter-training.voice',
    speed: 'recruiter-training.speed',
    volume: 'recruiter-training.volume',
};

function readStored(key: string): string | null {
    try {
        return window.localStorage.getItem(key);
    } catch {
        return null;
    }
}

function store(key: string, value: string) {
    try {
        window.localStorage.setItem(key, value);
    } catch {
        // Storage can be unavailable (private mode); preferences are optional.
    }
}

function clock(seconds: number): string {
    const safe = Math.max(0, Math.floor(Number.isFinite(seconds) ? seconds : 0));
    const minutes = Math.floor(safe / 60);
    const rest = safe % 60;

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}

function normaliseLocale(locale: string): string {
    return locale.replace('_', '-').toLowerCase();
}

const LANGUAGE_NAMES: Record<string, string> = { te: 'Telugu' };

function speechSupported(): boolean {
    return typeof window !== 'undefined' && 'speechSynthesis' in window && typeof SpeechSynthesisUtterance !== 'undefined';
}

/**
 * "Listen to Lesson". Nothing plays until the recruiter presses Play. Mounted
 * with a per-lesson key, so moving to another lesson stops and resets it.
 *
 * Two engines behind one set of controls: an audio file from a server-side
 * provider, or the browser's own speech (Web Speech API) reading the lesson
 * sentence by sentence. The browser engine's timeline is an estimate.
 *
 * Listening never completes a lesson; that stays an explicit button.
 */
export function TrainingAudioPlayer({ audio, language = 'en', initialSeconds = 0, onPositionReport }: Props) {
    const english = language === 'en';
    const voices = audio.voices_by_language?.[language] ?? (english ? audio.voices : []);
    const hasText = audio.has_text_by_language?.[language] ?? (english ? audio.has_text : false);
    const voiceStorageKey = english ? STORAGE.voice : `${STORAGE.voice}.${language}`;
    const languageName = LANGUAGE_NAMES[language] ?? language;
    const deviceDelivery = audio.delivery === 'device';
    const available = audio.delivery !== 'unavailable' && voices.length > 0;
    const unsupported = deviceDelivery && !speechSupported();

    const [voiceKey, setVoiceKey] = useState<string>(() => {
        const saved = readStored(voiceStorageKey);

        return voices.some((voice) => voice.key === saved) ? (saved as string) : (voices[0]?.key ?? '');
    });
    const [speed, setSpeed] = useState<number>(() => {
        const saved = Number(readStored(STORAGE.speed));

        return SPEEDS.includes(saved) ? saved : 1;
    });
    const [volume, setVolume] = useState<number>(() => {
        const saved = Number(readStored(STORAGE.volume));

        return saved > 0 && saved <= 1 ? saved : 1;
    });
    const [muted, setMuted] = useState(false);
    const [status, setStatus] = useState<Status>('idle');
    const [error, setError] = useState<string | null>(null);
    const [position, setPosition] = useState(0);
    const [duration, setDuration] = useState(0);
    const [estimated, setEstimated] = useState(deviceDelivery);
    const [deviceVoiceMissing, setDeviceVoiceMissing] = useState(false);

    const selectedVoice = voices.find((voice) => voice.key === voiceKey) ?? voices[0];

    // Shared refs, read inside browser callbacks.
    const payloadRef = useRef<SpeechPayload | null>(null);
    const positionRef = useRef(0);
    const pendingSeekRef = useRef(initialSeconds > 0 ? initialSeconds : 0);
    const speedRef = useRef(speed);
    const volumeRef = useRef(volume);
    const mutedRef = useRef(muted);
    const playingRef = useRef(false);
    const abortRef = useRef<AbortController | null>(null);
    const lastReportedRef = useRef<number | null>(null);
    const reportRef = useRef(onPositionReport);

    // Browser speech engine.
    const segmentsRef = useRef<string[]>([]);
    const startsRef = useRef<number[]>([]);
    const lengthsRef = useRef<number[]>([]);
    const indexRef = useRef(0);
    const segmentStartedAtRef = useRef(0);
    const utteranceRef = useRef<SpeechSynthesisUtterance | null>(null);
    const tickRef = useRef<number | null>(null);
    const volumeRestartRef = useRef<number | null>(null);

    // Audio file engine.
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const objectUrlRef = useRef<string | null>(null);

    useEffect(() => {
        reportRef.current = onPositionReport;
    }, [onPositionReport]);

    const updatePosition = useCallback((seconds: number) => {
        positionRef.current = seconds;
        setPosition(seconds);
    }, []);

    const report = useCallback(() => {
        const seconds = Math.floor(positionRef.current);

        if (reportRef.current && lastReportedRef.current !== seconds && (payloadRef.current !== null || seconds > 0)) {
            lastReportedRef.current = seconds;
            reportRef.current(seconds);
        }
    }, []);

    const stopTick = useCallback(() => {
        if (tickRef.current !== null) {
            window.clearInterval(tickRef.current);
            tickRef.current = null;
        }
    }, []);

    const stopEverything = useCallback(() => {
        playingRef.current = false;
        stopTick();
        abortRef.current?.abort();

        if (speechSupported()) {
            utteranceRef.current = null;
            window.speechSynthesis.cancel();
        }

        if (audioRef.current) {
            audioRef.current.pause();
        }
    }, [stopTick]);

    const fail = useCallback(
        (message: string) => {
            stopEverything();
            setError(message);
            setStatus('error');
        },
        [stopEverything],
    );

    // Voices on this device for the chosen locale (browser engine only).
    const deviceVoiceFor = useCallback((locale: string): SpeechSynthesisVoice | null => {
        if (!speechSupported()) {
            return null;
        }

        const wanted = normaliseLocale(locale);
        const installed = window.speechSynthesis.getVoices();
        const exact = installed.find((voice) => normaliseLocale(voice.lang) === wanted);

        if (exact || wanted.startsWith('en')) {
            return exact ?? null;
        }

        // Outside English any regional voice of the language will do (te-IN or plain te).
        const base = wanted.split('-')[0];

        return installed.find((voice) => normaliseLocale(voice.lang).split('-')[0] === base) ?? null;
    }, []);

    useEffect(() => {
        if (!deviceDelivery || !speechSupported() || !selectedVoice) {
            return;
        }

        const check = () => {
            const installed = window.speechSynthesis.getVoices();
            setDeviceVoiceMissing(installed.length > 0 && deviceVoiceFor(selectedVoice.locale) === null);
        };

        check();
        window.speechSynthesis.addEventListener('voiceschanged', check);

        return () => window.speechSynthesis.removeEventListener('voiceschanged', check);
    }, [deviceDelivery, selectedVoice, deviceVoiceFor]);

    // Stop and release everything when the lesson (and so this component) goes away.
    useEffect(() => {
        return () => {
            if (playingRef.current) {
                report();
            }

            stopEverything();

            if (volumeRestartRef.current !== null) {
                window.clearTimeout(volumeRestartRef.current);
            }

            if (objectUrlRef.current) {
                URL.revokeObjectURL(objectUrlRef.current);
            }

            audioRef.current = null;
        };
    }, [report, stopEverything]);

    // Periodic progress report while playing.
    useEffect(() => {
        if (status !== 'playing') {
            return;
        }

        const timer = window.setInterval(report, REPORT_EVERY_MS);

        return () => window.clearInterval(timer);
    }, [status, report]);

    const totalSeconds = useCallback(() => {
        if (deviceDelivery) {
            const starts = startsRef.current;
            const lengths = lengthsRef.current;

            return starts.length ? starts[starts.length - 1] + lengths[lengths.length - 1] : 0;
        }

        return audioRef.current && Number.isFinite(audioRef.current.duration) ? audioRef.current.duration : 0;
    }, [deviceDelivery]);

    // ---- Browser speech engine -------------------------------------------

    const prepareSegments = useCallback((payload: SpeechPayload) => {
        const segments = payload.segments;
        const words = segments.map((segment) => Math.max(1, segment.split(/\s+/).filter(Boolean).length));
        const totalWords = words.reduce((sum, count) => sum + count, 0);
        const total = payload.estimated_seconds > 0 ? payload.estimated_seconds : Math.ceil((totalWords / 150) * 60);

        let cursor = 0;
        const starts: number[] = [];
        const lengths: number[] = [];

        words.forEach((count) => {
            const length = totalWords > 0 ? (total * count) / totalWords : 0;
            starts.push(cursor);
            lengths.push(length);
            cursor += length;
        });

        segmentsRef.current = segments;
        startsRef.current = starts;
        lengthsRef.current = lengths;
        setDuration(cursor);
        setEstimated(true);
    }, []);

    const segmentAt = useCallback((seconds: number): number => {
        const starts = startsRef.current;
        let index = 0;

        for (let i = 0; i < starts.length; i++) {
            if (starts[i] <= seconds + 0.01) {
                index = i;
            }
        }

        return index;
    }, []);

    const speakFrom = useCallback(
        (index: number) => {
            const synth = window.speechSynthesis;
            const segments = segmentsRef.current;

            utteranceRef.current = null;
            synth.cancel();
            stopTick();

            if (index >= segments.length) {
                playingRef.current = false;
                updatePosition(totalSeconds());
                setStatus('ended');
                report();

                return;
            }

            indexRef.current = index;
            const voice = payloadRef.current?.voice ?? selectedVoice;
            const utterance = new SpeechSynthesisUtterance(segments[index]);
            utterance.lang = voice?.locale ?? 'en-IN';
            const installed = voice ? deviceVoiceFor(voice.locale) : null;

            if (installed) {
                utterance.voice = installed;
            }

            utterance.rate = speedRef.current;
            utterance.volume = mutedRef.current ? 0 : volumeRef.current;

            utterance.onend = () => {
                if (utteranceRef.current !== utterance || !playingRef.current) {
                    return;
                }

                speakFrom(index + 1);
            };

            utterance.onerror = (event) => {
                if (utteranceRef.current !== utterance || event.error === 'interrupted' || event.error === 'canceled') {
                    return;
                }

                fail('Your browser could not read this lesson aloud. Please try again.');
            };

            utteranceRef.current = utterance;
            playingRef.current = true;
            segmentStartedAtRef.current = performance.now();
            updatePosition(startsRef.current[index] ?? 0);
            synth.speak(utterance);

            tickRef.current = window.setInterval(() => {
                const start = startsRef.current[index] ?? 0;
                const length = lengthsRef.current[index] ?? 0;
                const elapsed = ((performance.now() - segmentStartedAtRef.current) / 1000) * speedRef.current;
                updatePosition(Math.min(start + elapsed, start + length));
            }, 250);
        },
        [deviceVoiceFor, fail, report, selectedVoice, stopTick, totalSeconds, updatePosition],
    );

    // ---- Audio file engine -----------------------------------------------

    const ensureAudioElement = useCallback((): HTMLAudioElement => {
        if (audioRef.current) {
            return audioRef.current;
        }

        const element = new Audio();
        element.preload = 'auto';
        element.addEventListener('timeupdate', () => updatePosition(element.currentTime));
        element.addEventListener('durationchange', () => setDuration(Number.isFinite(element.duration) ? element.duration : 0));
        element.addEventListener('play', () => {
            playingRef.current = true;
            setStatus('playing');
        });
        element.addEventListener('pause', () => {
            if (!element.ended) {
                playingRef.current = false;
                setStatus((current) => (current === 'error' ? current : 'paused'));
            }
        });
        element.addEventListener('ended', () => {
            playingRef.current = false;
            setStatus('ended');
            report();
        });
        element.addEventListener('error', () => {
            if (element.src) {
                fail('The lesson audio could not be played.');
            }
        });
        audioRef.current = element;

        return element;
    }, [fail, report, updatePosition]);

    // ---- Controls --------------------------------------------------------

    const loadSpeech = useCallback(async (): Promise<SpeechPayload | null> => {
        if (payloadRef.current && payloadRef.current.voice.key === voiceKey) {
            return payloadRef.current;
        }

        setStatus('loading');
        setError(null);
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        const url = new URL(audio.speech_url, window.location.origin);
        url.searchParams.set('voice', voiceKey);
        url.searchParams.set('language', language);

        try {
            const payload = await getJson<SpeechPayload>(url.toString(), controller.signal);

            if (!payload.has_text) {
                fail('This lesson has no written text to read aloud.');

                return null;
            }

            if (payload.delivery === 'device') {
                prepareSegments(payload);
            } else if (payload.delivery === 'audio' && payload.audio_url) {
                const blob = await getBlob(payload.audio_url, controller.signal);

                if (objectUrlRef.current) {
                    URL.revokeObjectURL(objectUrlRef.current);
                }

                objectUrlRef.current = URL.createObjectURL(blob);
                const element = ensureAudioElement();
                element.src = objectUrlRef.current;
                setEstimated(false);
            } else {
                fail('Listening to lessons is not available.');

                return null;
            }

            payloadRef.current = payload;

            return payload;
        } catch (caught) {
            if (caught instanceof DOMException && caught.name === 'AbortError') {
                return null;
            }

            fail(caught instanceof Error ? caught.message : 'The lesson audio could not be loaded.');

            return null;
        }
    }, [audio.speech_url, ensureAudioElement, fail, language, prepareSegments, voiceKey]);

    const play = useCallback(async () => {
        if (status === 'loading' || !available || unsupported) {
            return;
        }

        const payload = await loadSpeech();

        if (!payload) {
            return;
        }

        setError(null);

        const total = totalSeconds();
        let from = status === 'ended' ? 0 : positionRef.current;

        if (pendingSeekRef.current > 0) {
            from = pendingSeekRef.current < total - 2 ? pendingSeekRef.current : 0;
            pendingSeekRef.current = 0;
        }

        if (payload.delivery === 'device') {
            setStatus('playing');
            speakFrom(segmentAt(from));

            return;
        }

        const element = ensureAudioElement();
        element.playbackRate = speedRef.current;
        element.volume = volumeRef.current;
        element.muted = mutedRef.current;

        if (from > 0 && Math.abs(element.currentTime - from) > 1) {
            element.currentTime = from;
        }

        try {
            await element.play();
        } catch {
            fail('The lesson audio could not be played.');
        }
    }, [available, ensureAudioElement, fail, loadSpeech, segmentAt, speakFrom, status, totalSeconds, unsupported]);

    const pause = useCallback(() => {
        if (payloadRef.current?.delivery === 'device') {
            playingRef.current = false;
            utteranceRef.current = null;
            stopTick();
            window.speechSynthesis.cancel();
            updatePosition(startsRef.current[indexRef.current] ?? positionRef.current);
            setStatus('paused');
        } else {
            audioRef.current?.pause();
        }

        report();
    }, [report, stopTick, updatePosition]);

    const seekTo = useCallback(
        (seconds: number) => {
            const total = totalSeconds();
            const target = Math.max(0, Math.min(seconds, total > 0 ? total : seconds));

            if (!payloadRef.current) {
                pendingSeekRef.current = target;
                updatePosition(target);

                return;
            }

            if (payloadRef.current.delivery === 'device') {
                const index = segmentAt(target);

                if (playingRef.current) {
                    speakFrom(index);
                } else {
                    indexRef.current = index;
                    updatePosition(startsRef.current[index] ?? 0);

                    if (status === 'ended') {
                        setStatus('paused');
                    }
                }

                return;
            }

            if (audioRef.current) {
                audioRef.current.currentTime = target;
                updatePosition(target);

                if (status === 'ended') {
                    setStatus('paused');
                }
            }
        },
        [segmentAt, speakFrom, status, totalSeconds, updatePosition],
    );

    const previous = useCallback(() => {
        if (payloadRef.current?.delivery === 'device') {
            const index = indexRef.current;
            const intoSegment = positionRef.current - (startsRef.current[index] ?? 0);
            const target = intoSegment > 2 || index === 0 ? index : index - 1;
            seekTo(startsRef.current[target] ?? 0);

            return;
        }

        seekTo(positionRef.current - SKIP_SECONDS);
    }, [seekTo]);

    const next = useCallback(() => {
        if (payloadRef.current?.delivery === 'device') {
            const target = indexRef.current + 1;

            if (target < segmentsRef.current.length) {
                seekTo(startsRef.current[target]);
            }

            return;
        }

        seekTo(positionRef.current + SKIP_SECONDS);
    }, [seekTo]);

    const changeSpeed = (value: number) => {
        setSpeed(value);
        speedRef.current = value;
        store(STORAGE.speed, String(value));

        if (audioRef.current) {
            audioRef.current.playbackRate = value;
        }

        if (payloadRef.current?.delivery === 'device' && playingRef.current) {
            speakFrom(indexRef.current);
        }
    };

    const restartSpeechSoon = () => {
        if (payloadRef.current?.delivery !== 'device' || !playingRef.current) {
            return;
        }

        if (volumeRestartRef.current !== null) {
            window.clearTimeout(volumeRestartRef.current);
        }

        volumeRestartRef.current = window.setTimeout(() => {
            if (playingRef.current) {
                speakFrom(indexRef.current);
            }
        }, 350);
    };

    const changeVolume = (value: number) => {
        setVolume(value);
        volumeRef.current = value;
        store(STORAGE.volume, String(value));

        if (value > 0 && mutedRef.current) {
            setMuted(false);
            mutedRef.current = false;
        }

        if (audioRef.current) {
            audioRef.current.volume = value;
            audioRef.current.muted = mutedRef.current;
        }

        restartSpeechSoon();
    };

    const toggleMute = () => {
        const nextMuted = !muted;
        setMuted(nextMuted);
        mutedRef.current = nextMuted;

        if (audioRef.current) {
            audioRef.current.muted = nextMuted;
        }

        restartSpeechSoon();
    };

    const changeVoice = (key: string) => {
        if (key === voiceKey) {
            return;
        }

        if (playingRef.current) {
            report();
        }

        stopEverything();
        pendingSeekRef.current = positionRef.current;
        payloadRef.current = null;
        setVoiceKey(key);
        store(voiceStorageKey, key);
        setError(null);
        setStatus('idle');
    };

    const retry = () => {
        payloadRef.current = null;
        setError(null);
        setStatus('idle');
    };

    const isPlaying = status === 'playing';
    const isLoading = status === 'loading';
    const hasTimeline = duration > 0;
    const shownPosition = estimated ? position / speed : position;
    const shownDuration = estimated ? duration / speed : duration;
    // A non-English lesson cannot be read by the browser's English fallback voice.
    const missingLanguageVoice = deviceDelivery && !english && deviceVoiceMissing;
    const controlsDisabled = !available || unsupported || !hasText || missingLanguageVoice;

    const resumeHint = useMemo(() => {
        if (status !== 'idle' || initialSeconds <= 0) {
            return null;
        }

        return `Continues from ${clock(initialSeconds)}.`;
    }, [initialSeconds, status]);

    if (!available) {
        return (
            <section className="rounded-xl border border-dashed p-4" aria-label="Listen to lesson">
                <div className="text-muted-foreground flex items-center gap-2 text-sm">
                    <Headphones className="size-4" />
                    {english || audio.delivery === 'unavailable'
                        ? 'Listening to lessons is not available right now.'
                        : `Listening in ${languageName} is not available right now.`}
                </div>
            </section>
        );
    }

    return (
        <section className="rounded-xl border border-emerald-600/20 bg-emerald-50/40 p-4 dark:bg-emerald-950/10" aria-label="Listen to lesson">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-emerald-600/10 text-emerald-700">
                        <Headphones className="size-4" />
                    </span>
                    <div>
                        <h2 className="text-sm font-semibold">Listen to Lesson</h2>
                        <p className="text-muted-foreground text-xs">Press play to hear this lesson read aloud.</p>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <label htmlFor="training-voice" className="text-muted-foreground text-xs">
                        Voice
                    </label>
                    <Select value={voiceKey} onValueChange={changeVoice} disabled={isLoading}>
                        <SelectTrigger id="training-voice" className="h-8 w-48 bg-white/80" aria-label="Voice">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {voices.map((voice) => (
                                <SelectItem key={voice.key} value={voice.key}>
                                    {voice.flag ? `${voice.flag} ` : ''}
                                    {voice.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="mt-4 flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={previous}
                    disabled={controlsDisabled || !payloadRef.current}
                    aria-label="Previous"
                >
                    <SkipBack />
                </Button>
                <Button
                    type="button"
                    size="icon"
                    className="size-11 rounded-full bg-emerald-600 text-white hover:bg-emerald-700"
                    onClick={isPlaying ? pause : play}
                    disabled={controlsDisabled || isLoading}
                    aria-label={isPlaying ? 'Pause' : 'Play'}
                >
                    {isLoading ? <LoaderCircle className="animate-spin" /> : isPlaying ? <Pause /> : <Play />}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={next}
                    disabled={controlsDisabled || !payloadRef.current}
                    aria-label="Next"
                >
                    <SkipForward />
                </Button>

                <div className="ml-2 flex min-w-0 flex-1 items-center gap-3">
                    <span className="text-muted-foreground w-10 text-right text-xs tabular-nums">{clock(shownPosition)}</span>
                    <input
                        type="range"
                        min={0}
                        max={hasTimeline ? duration : 1}
                        step={0.5}
                        value={hasTimeline ? Math.min(position, duration) : 0}
                        onChange={(event) => seekTo(Number(event.target.value))}
                        disabled={controlsDisabled || !hasTimeline}
                        className="h-1.5 min-w-0 flex-1 cursor-pointer accent-emerald-600 disabled:cursor-default"
                        aria-label="Seek"
                        aria-valuetext={`${clock(shownPosition)} of ${clock(shownDuration)}`}
                    />
                    <span className="text-muted-foreground w-14 text-xs tabular-nums">
                        {hasTimeline ? `${estimated ? '≈' : ''}${clock(shownDuration)}` : '--:--'}
                    </span>
                </div>
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2">
                <div className="flex items-center gap-2">
                    <Button type="button" variant="ghost" size="icon" className="size-8" onClick={toggleMute} aria-label={muted ? 'Unmute' : 'Mute'}>
                        {muted || volume === 0 ? <VolumeX /> : <Volume2 />}
                    </Button>
                    <input
                        type="range"
                        min={0}
                        max={1}
                        step={0.05}
                        value={muted ? 0 : volume}
                        onChange={(event) => changeVolume(Number(event.target.value))}
                        className="h-1.5 w-28 cursor-pointer accent-emerald-600"
                        aria-label="Volume"
                    />
                </div>

                <div className="flex items-center gap-2">
                    <label htmlFor="training-speed" className="text-muted-foreground text-xs">
                        Speed
                    </label>
                    <Select value={String(speed)} onValueChange={(value) => changeSpeed(Number(value))}>
                        <SelectTrigger id="training-speed" className="h-8 w-24 bg-white/80" aria-label="Playback speed">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {SPEEDS.map((value) => (
                                <SelectItem key={value} value={String(value)}>
                                    {value}x
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {isLoading && <span className="text-muted-foreground text-xs">Preparing audio…</span>}
                {resumeHint && <span className="text-muted-foreground text-xs">{resumeHint}</span>}
                {status === 'ended' && <span className="text-muted-foreground text-xs">Finished. Mark the lesson complete when you are ready.</span>}
            </div>

            {unsupported && (
                <p className="text-destructive mt-3 flex items-start gap-2 text-sm">
                    <AlertCircle className="mt-0.5 size-4 shrink-0" />
                    This browser cannot read lessons aloud. Please use a current version of Chrome, Edge or Safari.
                </p>
            )}

            {!hasText && (
                <p className="text-muted-foreground mt-3 flex items-start gap-2 text-sm">
                    <Info className="mt-0.5 size-4 shrink-0" />
                    {english ? 'This lesson has no written text to read aloud yet.' : `This lesson has no ${languageName} text to read aloud yet.`}
                </p>
            )}

            {missingLanguageVoice && (
                <p className="mt-3 flex items-start gap-2 text-sm text-amber-700 dark:text-amber-300" role="status">
                    <AlertCircle className="mt-0.5 size-4 shrink-0" />
                    {languageName} voice is not available on this device. Please install or enable a {languageName} speech voice in your
                    system/browser.
                </p>
            )}

            {status === 'error' && error && (
                <div className="text-destructive mt-3 flex flex-wrap items-center gap-2 text-sm" role="alert">
                    <AlertCircle className="size-4 shrink-0" />
                    <span>{error}</span>
                    <Button type="button" variant="outline" size="sm" onClick={retry}>
                        <RotateCcw /> Try again
                    </Button>
                </div>
            )}

            {deviceDelivery && english && deviceVoiceMissing && selectedVoice && !unsupported && (
                <p className={cn('text-muted-foreground mt-3 flex items-start gap-2 text-xs')}>
                    <Info className="mt-0.5 size-3.5 shrink-0" />
                    This device has no {selectedVoice.label} voice installed, so your browser reads with its default English voice. Add an English (
                    {selectedVoice.locale}) voice in your device&apos;s speech settings to hear the {selectedVoice.label} accent.
                </p>
            )}
        </section>
    );
}
