import { useScrollSpy } from '@/components/recruiter-operations/training/training-course-navigation';
import {
    isPricedStage,
    LessonFlowchart,
    LessonScenario,
    LessonScript,
    scriptSteps,
} from '@/components/recruiter-operations/training/training-lesson-visuals';
import { cn } from '@/lib/utils';
import {
    BookMarked,
    BookOpen,
    CircleHelp,
    Info,
    Layers,
    Lightbulb,
    ListChecks,
    MessagesSquare,
    PhoneCall,
    Star,
    Target,
    TriangleAlert,
    Workflow,
    type LucideIcon,
} from 'lucide-react';
import { Fragment, useCallback, useEffect, useId, useMemo, useRef, useState, type ReactNode } from 'react';

export type LessonSectionKind =
    | 'objective'
    | 'content'
    | 'example'
    | 'reference'
    | 'mistakes'
    | 'practice'
    | 'note'
    | 'takeaway'
    | 'flow'
    | 'scenario'
    | 'script'
    | 'topic'
    | 'part';

export type LessonSection = {
    kind: LessonSectionKind | string;
    heading: string;
    body: string;
};

export interface LessonLanguage {
    code: string;
    label: string;
    native_label: string;
    canonical: boolean;
    available: boolean;
    structured: boolean;
    review_status: string | null;
    review_label: string | null;
    outdated: boolean;
    sections: LessonSection[] | null;
}

type Block =
    | { type: 'paragraph'; text: string }
    | { type: 'bullets' | 'numbered'; items: string[] }
    | { type: 'table'; header: string[] | null; rows: string[][] };

const tableCells = (line: string) =>
    line
        .trim()
        .replace(/^\|/, '')
        .replace(/\|$/, '')
        .split('|')
        .map((cell) => cell.trim());

const isSeparatorRow = (cells: string[]) => cells.length > 0 && cells.every((cell) => /^:?-{3,}:?$/.test(cell));

/**
 * The light lesson text format, mirrored from TrainingLessonStructure::blocks
 * on the server: each line is a paragraph, "- " a bullet, "1. " a numbered
 * item, "|" lines a table.
 */
export function lessonBlocks(body: string): Block[] {
    const blocks: Block[] = [];
    let table: string[][] = [];
    let list: { type: 'bullets' | 'numbered'; items: string[] } | null = null;

    const flushTable = () => {
        if (table.length === 0) {
            return;
        }

        const hasHeader = table.length > 1 && isSeparatorRow(table[1]);
        blocks.push({
            type: 'table',
            header: hasHeader ? table[0] : null,
            rows: (hasHeader ? table.slice(2) : table).filter((row) => !isSeparatorRow(row)),
        });
        table = [];
    };

    const flushList = () => {
        if (list) {
            blocks.push(list);
            list = null;
        }
    };

    for (const raw of body.replace(/\r\n?/g, '\n').split('\n')) {
        const line = raw.trim();

        if (line === '') {
            flushTable();
            flushList();
            continue;
        }

        if (line.startsWith('|')) {
            flushList();
            table.push(tableCells(line));
            continue;
        }

        flushTable();

        const bullet = /^[-•]\s+(.+)$/u.exec(line);
        const numbered = bullet ? null : /^\d{1,2}[.)]\s+(.+)$/u.exec(line);

        if (bullet || numbered) {
            const type = bullet ? 'bullets' : 'numbered';

            if (!list || list.type !== type) {
                flushList();
                list = { type, items: [] };
            }

            list.items.push((bullet ?? numbered)![1].trim());
            continue;
        }

        flushList();
        blocks.push({ type: 'paragraph', text: line });
    }

    flushTable();
    flushList();

    return blocks;
}

/** **bold** only; everything else is shown exactly as typed. */
function Inline({ text }: { text: string }) {
    const parts = text.split(/(\*\*[^*]+\*\*)/g);

    return (
        <>
            {parts.map((part, index) =>
                /^\*\*[^*]+\*\*$/.test(part) ? (
                    <strong key={index} className="text-foreground font-semibold">
                        {part.slice(2, -2)}
                    </strong>
                ) : (
                    <Fragment key={index}>{part}</Fragment>
                ),
            )}
        </>
    );
}

function LessonTable({ header, rows }: { header: string[] | null; rows: string[][] }) {
    return (
        <div className="overflow-hidden rounded-lg border">
            <table className="w-full table-fixed text-sm break-words">
                {header && (
                    <thead className="bg-muted/60">
                        <tr>
                            {header.map((cell, index) => (
                                <th key={index} className="px-3 py-2 text-left align-bottom font-semibold">
                                    <Inline text={cell} />
                                </th>
                            ))}
                        </tr>
                    </thead>
                )}
                <tbody>
                    {rows.map((row, rowIndex) => (
                        <tr key={rowIndex} className="even:bg-muted/30 border-t first:border-t-0">
                            {row.map((cell, cellIndex) => (
                                <td key={cellIndex} className={cn('px-3 py-1.5 align-top', cellIndex === 0 && row.length > 1 && 'font-medium')}>
                                    <Inline text={cell} />
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function LessonBlocks({ body, kind }: { body: string; kind?: string }) {
    return (
        <div className="space-y-3">
            {lessonBlocks(body).map((block, index) => {
                if (block.type === 'paragraph') {
                    return (
                        <p key={index}>
                            <Inline text={block.text} />
                        </p>
                    );
                }

                if (block.type === 'table') {
                    return <LessonTable key={index} header={block.header} rows={block.rows} />;
                }

                if (block.type === 'numbered') {
                    return (
                        <ol key={index} className="space-y-2">
                            {block.items.map((item, itemIndex) => (
                                <li key={itemIndex} className="flex gap-3">
                                    <span
                                        className={cn(
                                            'mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                            kind === 'practice'
                                                ? 'bg-violet-600/10 text-violet-700 dark:text-violet-300'
                                                : 'bg-muted text-foreground',
                                        )}
                                    >
                                        {itemIndex + 1}
                                    </span>
                                    <span className="min-w-0">
                                        <Inline text={item} />
                                    </span>
                                </li>
                            ))}
                        </ol>
                    );
                }

                return (
                    <ul key={index} className="space-y-1.5">
                        {block.items.map((item, itemIndex) => (
                            <li key={itemIndex} className="flex gap-2.5">
                                <span
                                    aria-hidden
                                    className={cn(
                                        'mt-[0.6rem] size-1.5 shrink-0 rounded-full',
                                        kind === 'mistakes' ? 'bg-rose-500' : kind === 'reference' ? 'bg-sky-600' : 'bg-emerald-600',
                                    )}
                                />
                                <span className="min-w-0">
                                    <Inline text={item} />
                                </span>
                            </li>
                        ))}
                    </ul>
                );
            })}
        </div>
    );
}

const STYLES: Record<string, { icon: LucideIcon; box: string; iconBox: string }> = {
    objective: {
        icon: Target,
        box: 'border-emerald-600/25 bg-emerald-50/60 dark:bg-emerald-950/20',
        iconBox: 'bg-emerald-600/10 text-emerald-700 dark:text-emerald-300',
    },
    content: { icon: BookOpen, box: 'bg-card', iconBox: 'bg-muted text-foreground' },
    reference: { icon: ListChecks, box: 'bg-card', iconBox: 'bg-sky-600/10 text-sky-700 dark:text-sky-300' },
    example: {
        icon: Lightbulb,
        box: 'border-sky-600/25 bg-sky-50/60 dark:bg-sky-950/20',
        iconBox: 'bg-sky-600/10 text-sky-700 dark:text-sky-300',
    },
    mistakes: {
        icon: TriangleAlert,
        box: 'border-rose-600/20 bg-rose-50/40 dark:bg-rose-950/10',
        iconBox: 'bg-rose-600/10 text-rose-700 dark:text-rose-300',
    },
    practice: {
        icon: CircleHelp,
        box: 'border-violet-600/20 bg-violet-50/40 dark:bg-violet-950/10',
        iconBox: 'bg-violet-600/10 text-violet-700 dark:text-violet-300',
    },
    note: {
        icon: Info,
        box: 'border-amber-500/30 bg-amber-50/60 dark:bg-amber-950/15',
        iconBox: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    },
    takeaway: {
        icon: Star,
        box: 'border-emerald-600/30 bg-emerald-600/[0.07] dark:bg-emerald-900/20',
        iconBox: 'bg-emerald-600 text-white',
    },
    flow: { icon: Workflow, box: 'bg-card', iconBox: 'bg-indigo-600/10 text-indigo-700 dark:text-indigo-300' },
    scenario: { icon: MessagesSquare, box: 'bg-card', iconBox: 'bg-amber-500/15 text-amber-700 dark:text-amber-300' },
    script: { icon: PhoneCall, box: 'bg-card', iconBox: 'bg-emerald-600/10 text-emerald-700 dark:text-emerald-300' },
    topic: { icon: Layers, box: 'bg-card', iconBox: 'bg-emerald-600 text-white' },
    part: { icon: BookMarked, box: 'bg-card', iconBox: 'bg-indigo-600 text-white' },
};

/** Explanatory text in the flow style; every other kind is a tinted callout. */
const PLAIN_KINDS = ['content', 'reference', 'flow', 'scenario', 'script'];

function SectionBody({
    section,
    navigate,
    stepAnchor,
}: {
    section: LessonSection;
    navigate?: (target: string) => boolean;
    stepAnchor?: (title: string) => string;
}) {
    const blocks = <LessonBlocks body={section.body} kind={section.kind} />;

    if (section.kind === 'flow') {
        return <LessonFlowchart body={section.body} fallback={blocks} navigate={navigate} />;
    }

    if (section.kind === 'scenario') {
        return <LessonScenario body={section.body} fallback={blocks} />;
    }

    if (section.kind === 'script') {
        return <LessonScript body={section.body} fallback={blocks} stepAnchor={stepAnchor} />;
    }

    return blocks;
}

export type LessonSectionsVariant = 'cards' | 'flow';

function SectionCard({
    section,
    headingLevel: Heading = 'h2',
    variant = 'cards',
    id,
    number,
    featured = false,
    children,
}: {
    section: LessonSection;
    headingLevel?: 'h2' | 'h3' | 'h4';
    variant?: LessonSectionsVariant;
    id?: string;
    number?: number;
    featured?: boolean;
    children: ReactNode;
}) {
    const style = STYLES[section.kind] ?? STYLES.content;
    const Icon = style.icon;
    const plain = PLAIN_KINDS.includes(section.kind) || !STYLES[section.kind];
    const className =
        variant === 'flow'
            ? plain
                ? 'py-1'
                : cn('rounded-r-lg border-0 border-l-4 px-4 py-3', style.box)
            : cn(
                  'rounded-xl border p-4 md:p-5',
                  featured ? 'border-2 border-emerald-600/50 bg-emerald-50/40 shadow-sm dark:bg-emerald-950/15' : style.box,
              );

    return (
        <section
            id={id}
            className={className}
            aria-label={section.heading}
            data-section-kind={section.kind}
            data-featured={featured ? 'true' : undefined}
        >
            <Heading className="mb-3 flex items-center gap-2.5 text-base font-semibold tracking-tight">
                <span
                    className={cn(
                        'flex size-7 shrink-0 items-center justify-center rounded-lg',
                        featured ? 'bg-emerald-600 text-white' : style.iconBox,
                    )}
                >
                    {number ? <span className="text-xs font-semibold tabular-nums">{number}</span> : <Icon className="size-4" />}
                </span>
                <span className={cn('min-w-0', featured && 'text-lg')}>{section.heading}</span>
                {featured && (
                    <span className="rounded-full bg-emerald-600 px-2.5 py-0.5 text-[11px] font-semibold tracking-wide text-white uppercase">
                        Core service
                    </span>
                )}
            </Heading>
            {children}
        </section>
    );
}

/**
 * A structured lesson: one card per section, styled by its kind. Text is
 * rendered as text, never as HTML.
 */
export function LessonSections({
    sections,
    lang,
    headingLevel,
    variant = 'cards',
}: {
    sections: LessonSection[];
    lang?: string;
    headingLevel?: 'h2' | 'h3' | 'h4';
    variant?: LessonSectionsVariant;
}) {
    if (
        sections.some((section) => isGroupKind(section.kind)) ||
        sections.filter((section) => section.kind === 'script').length >= PLAYBOOK_MIN_STAGES
    ) {
        return <CallPlaybook sections={sections} lang={lang} headingLevel={headingLevel} variant={variant} />;
    }

    return (
        <div lang={lang} className="text-foreground space-y-4 text-[15px] leading-7 break-words">
            {sections.map((section, index) => (
                <SectionCard key={`${index}-${section.heading}`} section={section} headingLevel={headingLevel} variant={variant}>
                    <SectionBody section={section} />
                </SectionCard>
            ))}
        </div>
    );
}

/** Lessons with this many call stages are shown as a calling playbook. */
const PLAYBOOK_MIN_STAGES = 3;

/** Kinds that frame the playbook rather than being a part of the call. */
const UNTABBED_KINDS = ['objective', 'note', 'takeaway'];

/** Topics and parts own the sections that follow them. */
const isGroupKind = (kind: string) => kind === 'topic' || kind === 'part';

const APP_HEADER_HEIGHT = 64;

const slug = (text: string) =>
    text
        .toLowerCase()
        .replace(/\*\*/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');

/** Where the playbook tabs stick: below the app header and, on the course page, the module tabs. */
function usePlaybookTop(): number {
    const [top, setTop] = useState(APP_HEADER_HEIGHT);

    useEffect(() => {
        const measure = () => {
            const modules = document.querySelector<HTMLElement>('nav[aria-label="Course modules"]');
            setTop(APP_HEADER_HEIGHT + (modules?.offsetHeight ?? 0));
        };

        measure();
        window.addEventListener('resize', measure);

        return () => window.removeEventListener('resize', measure);
    }, []);

    return top;
}

/**
 * A calling playbook: every stage of the call on one page, with sticky tabs
 * that follow the scroll position, and a call flow whose steps jump to the
 * matching stage or step.
 */
function CallPlaybook({
    sections,
    lang,
    headingLevel,
    variant,
}: {
    sections: LessonSection[];
    lang?: string;
    headingLevel?: 'h2' | 'h3' | 'h4';
    variant: LessonSectionsVariant;
}) {
    const base = `playbook-${useId().replace(/[^a-zA-Z0-9_-]/g, '')}`;
    const top = usePlaybookTop();
    const navRef = useRef<HTMLElement>(null);
    const barRef = useRef<HTMLDivElement>(null);
    const partsRef = useRef<HTMLDivElement>(null);
    const [barHeight, setBarHeight] = useState(52);
    const offset = top + barHeight + 12;

    const anchor = useCallback((index: number) => `${base}-${index}`, [base]);
    const firstTopic = sections.findIndex((section) => isGroupKind(section.kind));
    const topicMode = firstTopic >= 0;
    const stageKind = topicMode ? 'topic' : 'script';

    const parts = useMemo(
        () => sections.flatMap((section, index) => (section.kind === 'part' ? [{ section, index, id: `${base}-${index}` }] : [])),
        [sections, base],
    );
    /** 0 for sections before the first part, else the number of the part they belong to. */
    const groupOf = useCallback((index: number) => parts.filter((part) => part.index < index).length, [parts]);

    const tabs = useMemo(
        () =>
            sections.flatMap((section, index) =>
                UNTABBED_KINDS.includes(section.kind) || section.kind === 'part' || (topicMode && index > firstTopic && section.kind !== 'topic')
                    ? []
                    : [{ section, index, id: `${base}-${index}`, group: groupOf(index) }],
            ),
        [sections, base, topicMode, firstTopic, groupOf],
    );
    const stages = tabs.filter((tab) => tab.section.kind === stageKind);
    /** Stage numbers restart in each part. */
    const stageNumber = (index: number) => {
        const group = groupOf(index);
        const position = stages.filter((tab) => tab.group === group).findIndex((tab) => tab.index === index);

        return position >= 0 ? position + 1 : null;
    };

    /** In topic mode each topic or part owns the sections after it; the closing takeaway stays on its own. */
    const layout = useMemo(() => {
        const items: { index: number; children: number[] }[] = [];

        sections.forEach((section, index) => {
            const current = items[items.length - 1];

            if (topicMode && index > firstTopic && !isGroupKind(section.kind) && section.kind !== 'takeaway' && current) {
                current.children.push(index);
            } else {
                items.push({ index, children: [] });
            }
        });

        return items;
    }, [sections, topicMode, firstTopic]);
    const spyAnchors = useMemo(
        () => [...tabs, ...parts].sort((first, second) => first.index - second.index).map((anchor) => anchor.id),
        [tabs, parts],
    );
    const active = useScrollSpy(spyAnchors, offset);
    const activePart = parts.findIndex((part) => part.id === active);
    const activeGroup = activePart >= 0 ? activePart + 1 : (tabs.find((tab) => tab.id === active)?.group ?? 0);
    const groupTabs = parts.length > 0 ? tabs.filter((tab) => tab.group === activeGroup) : tabs;
    const overviewTab = tabs.find((tab) => tab.group === 0);

    const targets = useMemo(() => {
        const map = new Map<string, string>();

        sections.forEach((section, index) => {
            if (section.kind === 'script') {
                for (const step of scriptSteps(section.body)) {
                    if (!map.has(slug(step))) {
                        map.set(slug(step), `${anchor(index)}-${slug(step)}`);
                    }
                }
            }
        });
        sections.forEach((section, index) => map.set(slug(section.heading), anchor(index)));
        sections.forEach((section, index) => section.kind === 'topic' && map.set(slug(section.heading), anchor(index)));

        return map;
    }, [sections, anchor]);

    useEffect(() => {
        const nav = navRef.current;

        if (!nav || typeof ResizeObserver === 'undefined') {
            return;
        }

        const observer = new ResizeObserver(() => setBarHeight(nav.offsetHeight));
        observer.observe(nav);

        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        const bar = barRef.current;
        const tab = active ? bar?.querySelector<HTMLElement>(`[data-anchor="${active}"]`) : null;

        if (bar && tab) {
            bar.scrollTo({ left: Math.max(0, tab.offsetLeft - bar.clientWidth / 2 + tab.clientWidth / 2), behavior: 'smooth' });
        }
    }, [active, activeGroup]);

    useEffect(() => {
        const row = partsRef.current;
        const part = row?.querySelector<HTMLElement>('[aria-current="step"]');

        if (row && part) {
            row.scrollTo({ left: Math.max(0, part.offsetLeft - row.clientWidth / 2 + part.clientWidth / 2), behavior: 'smooth' });
        }
    }, [activeGroup]);

    const go = useCallback(
        (id: string) => {
            const element = document.getElementById(id);

            if (!element) {
                return false;
            }

            window.scrollTo({ top: Math.max(0, element.getBoundingClientRect().top + window.scrollY - offset), behavior: 'smooth' });
            element.animate?.([{ backgroundColor: 'rgba(5, 150, 105, 0.16)' }, { backgroundColor: 'transparent' }], {
                duration: 1600,
                easing: 'ease-out',
            });

            return true;
        },
        [offset],
    );

    const navigate = useCallback(
        (target: string) => {
            const id = targets.get(slug(target));

            return id ? go(id) : false;
        },
        [targets, go],
    );

    return (
        <div
            lang={lang}
            className="text-foreground space-y-4 text-[15px] leading-7 break-words"
            data-testid={topicMode ? 'lesson-playbook' : 'call-playbook'}
        >
            <nav
                ref={navRef}
                aria-label={topicMode ? 'Lesson topics' : 'Call playbook sections'}
                className="bg-background/95 sticky z-[9] -mx-1 border-b px-1 backdrop-blur-sm"
                style={{ top }}
            >
                {parts.length > 0 && (
                    <div ref={partsRef} className="overflow-x-auto overscroll-x-contain border-b border-dashed pt-2 pb-1.5 [scrollbar-width:thin]">
                        <ol className="flex w-max items-center gap-1" aria-label="Lesson parts">
                            {overviewTab && (
                                <li>
                                    <a
                                        href={`#${overviewTab.id}`}
                                        data-testid="playbook-part"
                                        aria-current={activeGroup === 0 ? 'step' : undefined}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            go(overviewTab.id);
                                        }}
                                        className={cn(
                                            'flex h-7 items-center rounded-md px-2.5 text-xs font-semibold whitespace-nowrap transition-colors',
                                            activeGroup === 0
                                                ? 'bg-indigo-600 text-white'
                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                        )}
                                    >
                                        Overview
                                    </a>
                                </li>
                            )}
                            {parts.map((part, position) => (
                                <li key={part.id}>
                                    <a
                                        href={`#${part.id}`}
                                        data-testid="playbook-part"
                                        aria-current={activeGroup === position + 1 ? 'step' : undefined}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            go(part.id);
                                        }}
                                        className={cn(
                                            'flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-semibold whitespace-nowrap transition-colors',
                                            activeGroup === position + 1
                                                ? 'bg-indigo-600 text-white'
                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                        )}
                                    >
                                        <BookMarked aria-hidden className="size-3.5" />
                                        {part.section.heading}
                                    </a>
                                </li>
                            ))}
                        </ol>
                    </div>
                )}
                <div ref={barRef} className="overflow-x-auto overscroll-x-contain [scrollbar-width:thin]">
                    <ol className="flex w-max items-center gap-0.5 py-2">
                        {groupTabs.map((tab, position) => {
                            const isActive = tab.id === active;
                            const stage = (stageNumber(tab.index) ?? 0) - 1;
                            const core = tab.section.kind === 'script' && isPricedStage(tab.section.body);
                            const Icon = (STYLES[tab.section.kind] ?? STYLES.content).icon;

                            return (
                                <li key={tab.id} className="flex items-center gap-0.5">
                                    {position > 0 && (
                                        <span aria-hidden className="text-muted-foreground/60 px-0.5 text-xs">
                                            →
                                        </span>
                                    )}
                                    <a
                                        href={`#${tab.id}`}
                                        data-anchor={tab.id}
                                        data-testid="playbook-tab"
                                        data-core={core ? 'true' : undefined}
                                        aria-current={isActive ? 'location' : undefined}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            go(tab.id);
                                        }}
                                        className={cn(
                                            'flex h-8 items-center gap-1.5 rounded-full px-2.5 text-[13px] whitespace-nowrap transition-colors',
                                            isActive
                                                ? 'bg-emerald-600 font-semibold text-white shadow-sm'
                                                : core
                                                  ? 'font-semibold text-emerald-800 ring-1 ring-emerald-600/50 hover:bg-emerald-50 dark:text-emerald-300'
                                                  : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                        )}
                                    >
                                        {stage >= 0 ? (
                                            <span
                                                aria-hidden
                                                className={cn(
                                                    'flex size-5 items-center justify-center rounded-full text-[11px] tabular-nums',
                                                    isActive ? 'bg-white/20' : 'bg-muted',
                                                )}
                                            >
                                                {stage + 1}
                                            </span>
                                        ) : (
                                            <Icon aria-hidden className="size-3.5" />
                                        )}
                                        {tab.section.heading}
                                    </a>
                                </li>
                            );
                        })}
                    </ol>
                </div>
            </nav>

            {layout.map(({ index, children }) => {
                const section = sections[index];
                const stage = (stageNumber(index) ?? 0) - 1;
                const card = (position: number, level: 'h2' | 'h3' | 'h4' | undefined) => {
                    const child = sections[position];
                    const framed = child.kind === 'script' || child.kind === 'flow';

                    return (
                        <SectionCard
                            key={`${position}-${child.heading}`}
                            id={anchor(position)}
                            number={position === index && stage >= 0 ? stage + 1 : undefined}
                            featured={child.kind === 'script' && isPricedStage(child.body)}
                            section={child}
                            headingLevel={level}
                            variant={framed ? 'cards' : variant}
                        >
                            <SectionBody section={child} navigate={navigate} stepAnchor={(title) => `${anchor(position)}-${slug(title)}`} />
                        </SectionCard>
                    );
                };

                if (section.kind === 'part') {
                    return (
                        <PartGroup key={`${index}-${section.heading}`} id={anchor(index)} section={section} headingLevel={headingLevel}>
                            {children.map((position) => card(position, headingLevel === 'h3' || headingLevel === 'h4' ? 'h4' : 'h3'))}
                        </PartGroup>
                    );
                }

                if (section.kind !== 'topic') {
                    return card(index, headingLevel);
                }

                return (
                    <TopicGroup
                        key={`${index}-${section.heading}`}
                        id={anchor(index)}
                        number={stage + 1}
                        section={section}
                        headingLevel={headingLevel}
                    >
                        {children.map((position) => card(position, headingLevel === 'h3' || headingLevel === 'h4' ? 'h4' : 'h3'))}
                    </TopicGroup>
                );
            })}
        </div>
    );
}

/** One part of a long lesson: a banner with its introduction and map, before the part's topics. */
function PartGroup({
    id,
    section,
    headingLevel: Heading = 'h2',
    children,
}: {
    id: string;
    section: LessonSection;
    headingLevel?: 'h2' | 'h3' | 'h4';
    children: ReactNode;
}) {
    return (
        <section
            id={id}
            aria-label={section.heading}
            data-section-kind="part"
            data-testid="lesson-part"
            className="space-y-4 rounded-xl border-2 border-indigo-600/30 bg-indigo-50/50 p-4 md:p-5 dark:bg-indigo-950/20"
        >
            <Heading className="flex items-center gap-3 text-xl font-semibold tracking-tight">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <BookMarked aria-hidden className="size-5" />
                </span>
                <span className="min-w-0">{section.heading}</span>
            </Heading>
            {section.body.trim() !== '' && (
                <div className="text-muted-foreground">
                    <LessonBlocks body={section.body} kind="part" />
                </div>
            )}
            {children}
        </section>
    );
}

/** One topic of a combined lesson: a numbered header with its key point, then the topic's own sections. */
function TopicGroup({
    id,
    number,
    section,
    headingLevel: Heading = 'h2',
    children,
}: {
    id: string;
    number: number;
    section: LessonSection;
    headingLevel?: 'h2' | 'h3' | 'h4';
    children: ReactNode;
}) {
    return (
        <section
            id={id}
            aria-label={section.heading}
            data-section-kind="topic"
            data-testid="lesson-topic"
            className="bg-card space-y-4 rounded-xl border p-4 md:p-5"
        >
            <Heading className="flex items-center gap-3 text-lg font-semibold tracking-tight">
                <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-sm font-semibold text-white tabular-nums">
                    {number}
                </span>
                <span className="min-w-0">{section.heading}</span>
            </Heading>
            {section.body.trim() !== '' && (
                <div className="rounded-lg border border-emerald-600/30 bg-emerald-600/[0.07] px-4 py-3 dark:bg-emerald-900/20">
                    <LessonBlocks body={section.body} kind="topic" />
                </div>
            )}
            {children}
        </section>
    );
}
