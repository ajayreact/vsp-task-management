import { cn } from '@/lib/utils';
import { BookOpen, CircleHelp, Info, Lightbulb, ListChecks, Star, Target, TriangleAlert, type LucideIcon } from 'lucide-react';
import { Fragment, type ReactNode } from 'react';

export type LessonSectionKind = 'objective' | 'content' | 'example' | 'reference' | 'mistakes' | 'practice' | 'note' | 'takeaway';

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
};

function SectionCard({ section, headingLevel: Heading = 'h2', children }: { section: LessonSection; headingLevel?: 'h2' | 'h3' | 'h4'; children: ReactNode }) {
    const style = STYLES[section.kind] ?? STYLES.content;
    const Icon = style.icon;

    return (
        <section className={cn('rounded-xl border p-4 md:p-5', style.box)} aria-label={section.heading}>
            <Heading className="mb-3 flex items-center gap-2.5 text-base font-semibold tracking-tight">
                <span className={cn('flex size-7 shrink-0 items-center justify-center rounded-lg', style.iconBox)}>
                    <Icon className="size-4" />
                </span>
                <span className="min-w-0">{section.heading}</span>
            </Heading>
            {children}
        </section>
    );
}

/**
 * A structured lesson: one card per section, styled by its kind. Text is
 * rendered as text, never as HTML.
 */
export function LessonSections({ sections, lang, headingLevel }: { sections: LessonSection[]; lang?: string; headingLevel?: 'h2' | 'h3' | 'h4' }) {
    return (
        <div lang={lang} className="text-foreground space-y-4 text-[15px] leading-7 break-words">
            {sections.map((section, index) => (
                <SectionCard key={`${index}-${section.heading}`} section={section} headingLevel={headingLevel}>
                    <LessonBlocks body={section.body} kind={section.kind} />
                </SectionCard>
            ))}
        </div>
    );
}
