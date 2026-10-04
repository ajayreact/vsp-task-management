import { cn } from '@/lib/utils';
import { ArrowDown, ArrowRight, Check, CircleCheck, CircleHelp, CircleX, Database, Info, Lightbulb, Mic, Siren, UserRound } from 'lucide-react';
import { Fragment, useMemo, useState, type ReactNode } from 'react';

const OWNER_STYLES: Record<string, string> = {
    recruiter: 'bg-emerald-600/10 text-emerald-800 ring-emerald-600/30 dark:text-emerald-300',
    candidate: 'bg-sky-600/10 text-sky-800 ring-sky-600/30 dark:text-sky-300',
    student: 'bg-sky-600/10 text-sky-800 ring-sky-600/30 dark:text-sky-300',
    dso: 'bg-violet-600/10 text-violet-800 ring-violet-600/30 dark:text-violet-300',
    uscis: 'bg-indigo-600/10 text-indigo-800 ring-indigo-600/30 dark:text-indigo-300',
    hr: 'bg-amber-500/15 text-amber-800 ring-amber-500/40 dark:text-amber-300',
    compliance: 'bg-rose-600/10 text-rose-800 ring-rose-600/30 dark:text-rose-300',
    employer: 'bg-teal-600/10 text-teal-800 ring-teal-600/30 dark:text-teal-300',
    'project manager': 'bg-orange-500/15 text-orange-800 ring-orange-500/40 dark:text-orange-300',
    'finance / payroll': 'bg-lime-600/15 text-lime-800 ring-lime-600/40 dark:text-lime-300',
    'core service': 'bg-emerald-600 text-white ring-emerald-700',
};

const isCore = (owner: string | null) => owner?.toLowerCase() === 'core service';

const FALLBACK_OWNER = 'bg-muted text-foreground ring-border';

function ownerStyle(owner: string): string {
    const key = owner.toLowerCase();

    return OWNER_STYLES[key] ?? Object.entries(OWNER_STYLES).find(([name]) => key.startsWith(name))?.[1] ?? FALLBACK_OWNER;
}

/** **bold** only, as in the lesson text format. */
function Inline({ text }: { text: string }) {
    return (
        <>
            {text.split(/(\*\*[^*]+\*\*)/g).map((part, index) =>
                /^\*\*[^*]+\*\*$/.test(part) ? (
                    <strong key={index} className="font-semibold">
                        {part.slice(2, -2)}
                    </strong>
                ) : (
                    <Fragment key={index}>{part}</Fragment>
                ),
            )}
        </>
    );
}

export interface FlowStep {
    owner: string | null;
    title: string;
    detail: string;
    target: string | null;
}

/**
 * Steps of a flow section: numbered lines "1. **Owner:** Step. Detail.".
 * The first sentence is the step; anything after it is the detail. A
 * trailing "[[Heading]]" links the step to a part of the same lesson.
 */
export function flowSteps(body: string): FlowStep[] {
    return body
        .replace(/\r\n?/g, '\n')
        .split('\n')
        .map((line) => /^\s*\d{1,2}[.)]\s+(.+)$/u.exec(line)?.[1].trim())
        .filter((item): item is string => !!item)
        .map((item) => {
            const link = /\s*\[\[([^\]]+)\]\]\s*$/u.exec(item);
            const linked = link ? item.slice(0, link.index).trim() : item;
            const owned = /^\*\*([^*]+?):\*\*\s*(.*)$/u.exec(linked);
            const text = owned ? owned[2] : linked;
            const split = /^(.+?[.!?])\s+(.+)$/u.exec(text);

            return {
                owner: owned ? owned[1].trim() : null,
                title: split ? split[1] : text,
                detail: split ? split[2] : '',
                target: link ? link[1].trim() : null,
            };
        });
}

const isDecision = (title: string) => title.trim().endsWith('?');

/**
 * A process flowchart. Steps read left to right on wide screens and top to
 * bottom on phones; each step opens to show its detail, and choosing an owner
 * highlights only the steps that person or office is responsible for. With
 * `navigate`, a linked step jumps to its part of the lesson instead.
 */
export function LessonFlowchart({ body, fallback, navigate }: { body: string; fallback: ReactNode; navigate?: (target: string) => boolean }) {
    const steps = useMemo(() => flowSteps(body), [body]);
    const owners = useMemo(() => [...new Set(steps.map((step) => step.owner).filter((owner): owner is string => !!owner))], [steps]);
    const [owner, setOwner] = useState<string | null>(null);
    const [open, setOpen] = useState<Set<number>>(new Set());
    const allOpen = open.size === steps.length;

    if (steps.length === 0) {
        return <>{fallback}</>;
    }

    const toggle = (index: number) =>
        setOpen((current) => {
            const next = new Set(current);

            if (next.has(index)) {
                next.delete(index);
            } else {
                next.add(index);
            }

            return next;
        });

    return (
        <div className="space-y-3" data-testid="lesson-flowchart">
            <div className="flex flex-wrap items-center gap-1.5" role="group" aria-label="Highlight steps by owner">
                {owners.length > 1 && (
                    <>
                        <button
                            type="button"
                            onClick={() => setOwner(null)}
                            aria-pressed={owner === null}
                            className={cn(
                                'rounded-full px-2.5 py-1 text-xs font-medium ring-1 transition-colors',
                                owner === null
                                    ? 'bg-foreground text-background ring-foreground'
                                    : 'text-muted-foreground ring-border hover:text-foreground',
                            )}
                        >
                            All steps
                        </button>
                        {owners.map((name) => (
                            <button
                                key={name}
                                type="button"
                                onClick={() => setOwner(owner === name ? null : name)}
                                aria-pressed={owner === name}
                                data-testid="flow-owner"
                                className={cn(
                                    'rounded-full px-2.5 py-1 text-xs font-medium ring-1 transition-opacity',
                                    ownerStyle(name),
                                    owner !== null && owner !== name && 'opacity-50',
                                )}
                            >
                                {name}
                            </button>
                        ))}
                    </>
                )}
                {navigate && steps.some((step) => step.target) && (
                    <p className="text-muted-foreground text-xs">Click a step to jump to that part of the call.</p>
                )}
                {steps.some((step) => step.detail && !(navigate && step.target)) && (
                    <button
                        type="button"
                        onClick={() => setOpen(allOpen ? new Set() : new Set(steps.map((_, index) => index)))}
                        className="text-muted-foreground hover:text-foreground ml-auto text-xs font-medium underline-offset-2 hover:underline"
                    >
                        {allOpen ? 'Hide details' : 'Show all details'}
                    </button>
                )}
            </div>

            <ol className={cn('grid gap-2 sm:grid-cols-2 sm:gap-3', steps.length > 9 ? 'lg:grid-cols-3 xl:grid-cols-4' : 'xl:grid-cols-3')}>
                {steps.map((step, index) => {
                    const dimmed = owner !== null && step.owner !== owner;
                    const linked = !!(navigate && step.target);
                    const expanded = linked || open.has(index);
                    const last = index === steps.length - 1;
                    const decision = isDecision(step.title);

                    return (
                        <li
                            key={index}
                            className="flex min-w-0 flex-col"
                            data-testid="flow-step"
                            data-owner={step.owner ?? ''}
                            data-target={step.target ?? undefined}
                            data-decision={decision ? 'true' : undefined}
                        >
                            <button
                                type="button"
                                onClick={() => (linked ? navigate!(step.target!) : step.detail && toggle(index))}
                                aria-expanded={step.detail && !linked ? expanded : undefined}
                                aria-label={linked ? `${step.title.replace(/\*\*/g, '')} — go to this part of the call` : undefined}
                                className={cn(
                                    'bg-card relative flex h-full min-w-0 flex-col gap-2 rounded-xl border p-3 text-left transition-all',
                                    (step.detail || linked) && 'hover:border-foreground/30 cursor-pointer',
                                    linked &&
                                        'hover:bg-emerald-50/60 hover:shadow-sm focus-visible:ring-2 focus-visible:ring-emerald-600/50 dark:hover:bg-emerald-950/20',
                                    decision && 'border-amber-500/50 bg-amber-50/50 dark:bg-amber-950/15',
                                    isCore(step.owner) && 'border-emerald-600/60 bg-emerald-50/70 ring-2 ring-emerald-600/30 dark:bg-emerald-950/25',
                                    dimmed && 'opacity-35',
                                    owner !== null && !dimmed && 'ring-2 ring-emerald-600/40',
                                )}
                            >
                                <span className="flex items-center gap-2">
                                    <span
                                        className={cn(
                                            'flex size-6 shrink-0 items-center justify-center text-xs font-semibold tabular-nums',
                                            decision
                                                ? 'rotate-45 rounded-[4px] bg-amber-500 text-white'
                                                : 'bg-foreground text-background rounded-full',
                                        )}
                                    >
                                        <span className={cn(decision && '-rotate-45')}>{index + 1}</span>
                                    </span>
                                    {decision && (
                                        <span className="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-500/40 dark:text-amber-300">
                                            Decision
                                        </span>
                                    )}
                                    {step.owner && (
                                        <span
                                            className={cn(
                                                'truncate rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1',
                                                ownerStyle(step.owner),
                                            )}
                                        >
                                            {step.owner}
                                        </span>
                                    )}
                                    {!last && <ArrowRight className="text-muted-foreground ml-auto hidden size-4 shrink-0 sm:block" aria-hidden />}
                                </span>
                                <span className="text-sm leading-snug font-medium">
                                    <Inline text={step.title.replace(/\.$/, '')} />
                                </span>
                                {step.detail && expanded && (
                                    <span className="text-muted-foreground text-[13px] leading-relaxed">
                                        <Inline text={step.detail} />
                                    </span>
                                )}
                                {step.detail && !expanded && <span className="text-muted-foreground text-[11px]">Tap for details</span>}
                                {linked && (
                                    <span className="mt-auto inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 dark:text-emerald-300">
                                        Go to step <ArrowRight className="size-3" aria-hidden />
                                    </span>
                                )}
                            </button>
                            {!last && <ArrowDown className="text-muted-foreground mx-auto my-0.5 size-4 shrink-0 sm:hidden" aria-hidden />}
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

const SCENARIO_PARTS: { match: RegExp; tone: string; icon: typeof Info; label: string }[] = [
    { match: /^(situation|candidate asks|scenario)/i, tone: 'border-border bg-muted/40', icon: CircleHelp, label: 'Situation' },
    { match: /^correct/i, tone: 'border-emerald-600/30 bg-emerald-50/70 dark:bg-emerald-950/20', icon: CircleCheck, label: 'Correct response' },
    { match: /^(incorrect|wrong)/i, tone: 'border-rose-600/30 bg-rose-50/60 dark:bg-rose-950/15', icon: CircleX, label: 'Incorrect response' },
    { match: /^(why|explanation)/i, tone: 'border-sky-600/25 bg-sky-50/60 dark:bg-sky-950/15', icon: Info, label: 'Why' },
    { match: /^escalat/i, tone: 'border-amber-500/40 bg-amber-50/70 dark:bg-amber-950/15', icon: Siren, label: 'Escalation' },
];

/**
 * A scenario: the situation, the correct and incorrect responses side by side
 * on wide screens, then the reason and who to escalate to.
 */
export function LessonScenario({ body, fallback }: { body: string; fallback: ReactNode }) {
    const parts = body
        .replace(/\r\n?/g, '\n')
        .split('\n')
        .map((line) => /^\s*\*\*([^*]+?):\*\*\s*(.+)$/u.exec(line))
        .filter((match): match is RegExpExecArray => match !== null)
        .map((match) => {
            const style = SCENARIO_PARTS.find((part) => part.match.test(match[1].trim()));

            return { label: match[1].trim(), text: match[2].trim(), style };
        });

    if (parts.length === 0) {
        return <>{fallback}</>;
    }

    const pair = parts.filter((part) => part.style && /^(correct|incorrect|wrong)/i.test(part.label));

    const card = (part: (typeof parts)[number], key: number) => {
        const Icon = part.style?.icon ?? Info;

        return (
            <div key={key} className={cn('min-w-0 rounded-lg border p-3', part.style?.tone ?? 'bg-card')} data-testid="scenario-part">
                <div className="mb-1 flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
                    <Icon className="size-3.5" aria-hidden />
                    {part.label}
                </div>
                <p className="text-[15px] leading-7">
                    <Inline text={part.text} />
                </p>
            </div>
        );
    };

    return (
        <div className="space-y-2.5" data-testid="lesson-scenario">
            {parts.filter((part) => !pair.includes(part) && /^(situation|candidate asks|scenario)/i.test(part.label)).map(card)}
            {pair.length > 0 && <div className="grid gap-2.5 md:grid-cols-2">{pair.map(card)}</div>}
            {parts.filter((part) => !pair.includes(part) && !/^(situation|candidate asks|scenario)/i.test(part.label)).map(card)}
        </div>
    );
}

type ScriptPart = { type: 'step'; title: string } | { type: 'group'; label: string; text: string; items: string[] } | { type: 'text'; text: string };

/**
 * Parts of a call stage: "**Visa / Status**" lines start a step, "**Say:**
 * ..." lines start a labelled group, and "- " items belong to the group
 * above them.
 */
export function scriptParts(body: string): ScriptPart[] {
    const parts: ScriptPart[] = [];

    for (const raw of body.replace(/\r\n?/g, '\n').split('\n')) {
        const line = raw.trim();

        if (line === '') {
            continue;
        }

        const label = /^\*\*([^*]+?):\*\*\s*(.*)$/u.exec(line);
        const step = label ? null : /^\*\*([^*]+)\*\*$/u.exec(line);
        const item = step || label ? null : /^[-•]\s+(.+)$/u.exec(line);
        const last = parts[parts.length - 1];

        if (step) {
            parts.push({ type: 'step', title: step[1].trim() });
        } else if (label) {
            parts.push({ type: 'group', label: label[1].trim(), text: label[2].trim(), items: [] });
        } else if (item && last?.type === 'group') {
            last.items.push(item[1].trim());
        } else if (item) {
            parts.push({ type: 'group', label: '', text: '', items: [item[1].trim()] });
        } else {
            parts.push({ type: 'text', text: line });
        }
    }

    return parts;
}

/** The steps inside a call stage, so a flowchart can link to them. */
export function scriptSteps(body: string): string[] {
    return scriptParts(body).flatMap((part) => (part.type === 'step' ? [part.title] : []));
}

const PRICE_LABEL = /^(price|breakdown|calculation)$/i;

/** A call stage that quotes a price is one of the core services. */
export const isPricedStage = (body: string) => scriptParts(body).some((part) => part.type === 'group' && /^price$/i.test(part.label));

const groupKind = (label: string): 'say' | 'responses' | 'crm' | 'tip' | 'price' | 'list' => {
    if (PRICE_LABEL.test(label)) {
        return 'price';
    }

    if (/^(say|ask|you say|then say|say next)/i.test(label)) {
        return 'say';
    }

    if (/(may say|they say|responses|if they)/i.test(label)) {
        return 'responses';
    }

    if (/(record|crm)/i.test(label)) {
        return 'crm';
    }

    if (/^(tip|remember|note|never|avoid|important)/i.test(label)) {
        return 'tip';
    }

    return 'list';
};

function SayBlock({ label, lines }: { label: string; lines: string[] }) {
    return (
        <div className="rounded-lg border border-emerald-600/30 bg-emerald-50/70 p-3 dark:bg-emerald-950/20" data-testid="script-say">
            <div className="mb-1.5 flex items-center gap-1.5 text-[11px] font-semibold tracking-wider text-emerald-800 uppercase dark:text-emerald-300">
                <Mic className="size-3.5" aria-hidden />
                {label}
            </div>
            <div className="space-y-1.5">
                {lines.map((line, index) => (
                    <p key={index} className="text-foreground text-base leading-7 font-medium md:text-[17px]">
                        <Inline text={line} />
                    </p>
                ))}
            </div>
        </div>
    );
}

function ResponseList({ label, text, items }: { label: string; text: string; items: string[] }) {
    return (
        <div className="space-y-2" data-testid="script-responses">
            <div className="text-muted-foreground flex items-center gap-1.5 text-[11px] font-semibold tracking-wider uppercase">
                <UserRound className="size-3.5" aria-hidden />
                {label}
            </div>
            {text && (
                <p className="text-sm">
                    <Inline text={text} />
                </p>
            )}
            <ul className="space-y-2">
                {items.map((item, index) => {
                    const pair = /^\*\*(?:they say|consultant|if they say):\*\*\s*(.+?)\s*\*\*(?:you say|say next|then say|you):\*\*\s*(.+)$/iu.exec(
                        item,
                    );

                    if (!pair) {
                        return (
                            <li key={index} className="bg-card rounded-lg border px-3 py-2 text-sm">
                                <Inline text={item} />
                            </li>
                        );
                    }

                    return (
                        <li key={index} className="grid overflow-hidden rounded-lg border md:grid-cols-2" data-testid="script-response">
                            <div className="bg-sky-50/70 px-3 py-2 dark:bg-sky-950/20">
                                <div className="text-[11px] font-semibold tracking-wide text-sky-800 uppercase dark:text-sky-300">If they say</div>
                                <p className="text-sm leading-6">
                                    <Inline text={pair[1]} />
                                </p>
                            </div>
                            <div className="bg-card border-t px-3 py-2 md:border-t-0 md:border-l">
                                <div className="flex items-center gap-1 text-[11px] font-semibold tracking-wide text-emerald-800 uppercase dark:text-emerald-300">
                                    You say next <ArrowRight className="size-3" aria-hidden />
                                </div>
                                <p className="text-sm leading-6">
                                    <Inline text={pair[2]} />
                                </p>
                            </div>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

/**
 * A price, a breakdown ("- Payroll amount: $1,934.17") or a calculation,
 * shown large so the recruiter quotes the exact figures.
 */
function PriceBlock({ label, text, items }: { label: string; text: string; items: string[] }) {
    const rows = items.map((item) => {
        const split = /^(.+):\s*(.+)$/u.exec(item.replace(/\*\*/g, ''));

        return split ? { name: split[1], value: split[2] } : { name: item.replace(/\*\*/g, ''), value: '' };
    });

    return (
        <div className="rounded-xl border-2 border-emerald-600/40 bg-white p-4 shadow-sm dark:bg-emerald-950/20" data-testid="script-price">
            <div className="text-[11px] font-semibold tracking-wider text-emerald-800 uppercase dark:text-emerald-300">{label}</div>
            {text && (
                <p className={cn('text-foreground font-semibold tabular-nums', /^price$/i.test(label) ? 'text-2xl md:text-3xl' : 'text-lg')}>
                    <Inline text={text} />
                </p>
            )}
            {rows.length > 0 && (
                <dl className="mt-2 divide-y rounded-lg border">
                    {rows.map((row, index) => (
                        <div
                            key={index}
                            className={cn(
                                'flex items-baseline justify-between gap-3 px-3 py-2 text-sm',
                                /total/i.test(row.name) && 'bg-emerald-600/[0.07] font-semibold',
                            )}
                        >
                            <dt>{row.name}</dt>
                            <dd className="font-semibold whitespace-nowrap tabular-nums">{row.value}</dd>
                        </div>
                    ))}
                </dl>
            )}
        </div>
    );
}

/** A checklist the recruiter can tick during the call; nothing is saved. */
function Checklist({ label, text, items, tone }: { label: string; text: string; items: string[]; tone: 'crm' | 'list' }) {
    const [done, setDone] = useState<Set<number>>(new Set());
    const Icon = tone === 'crm' ? Database : CircleCheck;

    return (
        <div
            className={cn('rounded-lg border p-3', tone === 'crm' ? 'border-sky-600/25 bg-sky-50/60 dark:bg-sky-950/15' : 'bg-muted/30')}
            data-testid={tone === 'crm' ? 'script-crm' : 'script-checklist'}
        >
            {label && (
                <div
                    className={cn(
                        'mb-1.5 flex items-center gap-1.5 text-[11px] font-semibold tracking-wider uppercase',
                        tone === 'crm' ? 'text-sky-800 dark:text-sky-300' : 'text-muted-foreground',
                    )}
                >
                    <Icon className="size-3.5" aria-hidden />
                    {label}
                </div>
            )}
            {text && (
                <p className="mb-1.5 text-sm">
                    <Inline text={text} />
                </p>
            )}
            <ul className="space-y-1">
                {items.map((item, index) => {
                    const checked = done.has(index);

                    return (
                        <li key={index}>
                            <button
                                type="button"
                                role="checkbox"
                                aria-checked={checked}
                                onClick={() =>
                                    setDone((current) => {
                                        const next = new Set(current);

                                        if (!next.delete(index)) {
                                            next.add(index);
                                        }

                                        return next;
                                    })
                                }
                                className="flex w-full items-start gap-2 rounded px-1 py-0.5 text-left text-sm leading-6 hover:bg-black/[0.03] dark:hover:bg-white/[0.04]"
                            >
                                <span
                                    aria-hidden
                                    className={cn(
                                        'mt-1 flex size-4 shrink-0 items-center justify-center rounded border',
                                        checked ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-foreground/30 bg-background',
                                    )}
                                >
                                    {checked && <Check className="size-3" />}
                                </span>
                                <span className={cn('min-w-0', checked && 'text-muted-foreground line-through')}>
                                    <Inline text={item} />
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

/**
 * One stage of a call script: what to say, what the consultant may answer and
 * what to say next, and what to record in the CRM.
 */
export function LessonScript({ body, fallback, stepAnchor }: { body: string; fallback: ReactNode; stepAnchor?: (title: string) => string }) {
    const parts = useMemo(() => scriptParts(body), [body]);

    if (!parts.some((part) => part.type === 'group' && part.label !== '')) {
        return <>{fallback}</>;
    }

    return (
        <div className="space-y-3" data-testid="lesson-script">
            {parts.map((part, index) => {
                if (part.type === 'step') {
                    return (
                        <h5
                            key={index}
                            id={stepAnchor?.(part.title)}
                            className="flex items-center gap-2 pt-2 text-sm font-semibold tracking-tight first:pt-0"
                            data-testid="script-step"
                        >
                            <span aria-hidden className="h-px w-4 bg-emerald-600/50" />
                            {part.title}
                            <span aria-hidden className="bg-border h-px flex-1" />
                        </h5>
                    );
                }

                if (part.type === 'text') {
                    return (
                        <p key={index} className="text-sm leading-6">
                            <Inline text={part.text} />
                        </p>
                    );
                }

                const kind = groupKind(part.label);

                if (kind === 'say') {
                    return <SayBlock key={index} label={part.label} lines={[part.text, ...part.items].filter((line) => line !== '')} />;
                }

                if (kind === 'price') {
                    return <PriceBlock key={index} label={part.label} text={part.text} items={part.items} />;
                }

                if (kind === 'responses') {
                    return <ResponseList key={index} label={part.label} text={part.text} items={part.items} />;
                }

                if (kind === 'tip') {
                    return (
                        <div
                            key={index}
                            className="flex gap-2 rounded-lg border border-amber-500/35 bg-amber-50/60 p-3 text-sm leading-6 dark:bg-amber-950/15"
                            data-testid="script-tip"
                        >
                            <Lightbulb className="mt-0.5 size-4 shrink-0 text-amber-600" aria-hidden />
                            <div className="min-w-0">
                                <strong className="font-semibold">{part.label}:</strong> <Inline text={part.text} />
                                {part.items.length > 0 && (
                                    <ul className="mt-1 list-disc space-y-0.5 pl-4">
                                        {part.items.map((item, itemIndex) => (
                                            <li key={itemIndex}>
                                                <Inline text={item} />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    );
                }

                return <Checklist key={index} label={part.label} text={part.text} items={part.items} tone={kind === 'crm' ? 'crm' : 'list'} />;
            })}
        </div>
    );
}
