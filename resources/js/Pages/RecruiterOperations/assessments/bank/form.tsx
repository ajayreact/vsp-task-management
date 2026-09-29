import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { type ManagerQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, X } from 'lucide-react';
import { type FormEvent } from 'react';

interface Context {
    version_id: number;
    version_label: string;
    assessment_id: number;
    assessment_title: string;
}

interface Props {
    question: ManagerQuestion | null;
    context: Context | null;
    types: Option[];
    categories: string[];
}

type OptionRow = { text: string; is_correct: boolean };

type FormData = {
    type: string;
    prompt: string;
    points: number | '';
    explanation: string;
    category: string;
    options: OptionRow[];
    add_another: boolean;
};

const MAX_OPTIONS = 26;
const TRUE_FALSE: OptionRow[] = [
    { text: 'True', is_correct: true },
    { text: 'False', is_correct: false },
];

function optionsFor(type: string, current: OptionRow[]): OptionRow[] {
    if (type === 'short_answer') {
        return [];
    }

    if (type === 'true_false') {
        const correctIsFalse = current.length === 2 && current[1]?.is_correct && !current[0]?.is_correct;

        return TRUE_FALSE.map((option, index) => ({ ...option, is_correct: correctIsFalse ? index === 1 : index === 0 }));
    }

    if (current.length >= 2 && !(current.length === 2 && current[0]?.text === 'True' && current[1]?.text === 'False')) {
        return type === 'single_choice' ? keepOneCorrect(current) : current;
    }

    return [
        { text: '', is_correct: true },
        { text: '', is_correct: false },
        { text: '', is_correct: false },
        { text: '', is_correct: false },
    ];
}

function keepOneCorrect(options: OptionRow[]): OptionRow[] {
    const first = options.findIndex((option) => option.is_correct);

    return options.map((option, index) => ({ ...option, is_correct: index === (first === -1 ? 0 : first) }));
}

const letter = (index: number) => String.fromCharCode(65 + index);

export default function QuestionForm({ question, context, types, categories }: Props) {
    const editing = question !== null;
    const backUrl = context
        ? `/recruiter/assessments/manage/${context.assessment_id}?version=${context.version_id}`
        : editing
          ? `/recruiter/assessments/questions/${question.id}`
          : '/recruiter/assessments/questions';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        context ? { title: `${context.assessment_title} · ${context.version_label}`, href: backUrl } : { title: 'Question Bank', href: '/recruiter/assessments/questions' },
        { title: editing ? 'Edit question' : 'New question', href: '#' },
    ];

    const { data, setData, post, put, processing, errors, transform } = useForm<FormData>({
        type: question?.type ?? 'single_choice',
        prompt: question?.prompt ?? '',
        points: question?.points ?? 1,
        explanation: question?.explanation ?? '',
        category: question?.category ?? '',
        options: question ? question.options.map((option) => ({ text: option.text, is_correct: option.is_correct })) : optionsFor('single_choice', []),
        add_another: false,
    });

    const errorBag = errors as Record<string, string | undefined>;
    const fixedOptions = data.type === 'true_false';
    const usesOptions = data.type !== 'short_answer';

    const changeType = (type: string) => {
        setData((current) => ({ ...current, type, options: optionsFor(type, current.options) }));
    };

    const updateOption = (index: number, changes: Partial<OptionRow>) => {
        setData(
            'options',
            data.options.map((option, i) => {
                if (i === index) {
                    return { ...option, ...changes };
                }

                return changes.is_correct && data.type !== 'multiple_choice' ? { ...option, is_correct: false } : option;
            }),
        );
    };

    const save = (addAnother: boolean) => (event?: FormEvent) => {
        event?.preventDefault();
        transform((current) => ({ ...current, add_another: addAnother, options: usesOptions ? current.options : [] }));

        if (editing) {
            put(`/recruiter/assessments/questions/${question.id}`);
        } else if (context) {
            post(`/recruiter/assessments/manage/versions/${context.version_id}/questions`);
        } else {
            post('/recruiter/assessments/questions');
        }
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={editing ? 'Edit question' : 'New question'} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={editing ? 'Edit question' : 'New question'}
                    description={
                        context
                            ? `This question belongs only to ${context.assessment_title} · ${context.version_label} (a draft).`
                            : 'Question Bank items are copied into quizzes. Editing one here does not change quizzes that already use it.'
                    }
                />

                <form onSubmit={save(false)} className="max-w-3xl space-y-6">
                    <Card>
                        <CardContent className="space-y-4 pt-6">
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="type">Type</Label>
                                    <Select value={data.type} onValueChange={changeType}>
                                        <SelectTrigger id="type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {types.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.type} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="points">Points</Label>
                                    <Input
                                        id="points"
                                        type="number"
                                        min={1}
                                        max={100}
                                        value={data.points}
                                        onChange={(event) => setData('points', event.target.value === '' ? '' : Number(event.target.value))}
                                    />
                                    <InputError message={errors.points} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="prompt">Question</Label>
                                <Textarea id="prompt" rows={3} value={data.prompt} onChange={(event) => setData('prompt', event.target.value)} maxLength={2000} required />
                                <InputError message={errors.prompt} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="category">Category</Label>
                                <Input
                                    id="category"
                                    list="question-categories"
                                    value={data.category}
                                    maxLength={100}
                                    onChange={(event) => setData('category', event.target.value)}
                                    placeholder="Optional, e.g. Job Description Analysis"
                                />
                                <datalist id="question-categories">
                                    {categories.map((category) => (
                                        <option key={category} value={category} />
                                    ))}
                                </datalist>
                                <InputError message={errors.category} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{usesOptions ? 'Options' : 'Answer'}</CardTitle>
                            <CardDescription>
                                {data.type === 'multiple_choice' && 'Tick every correct option. A response scores only when it matches all of them exactly.'}
                                {data.type === 'single_choice' && 'Tick the one correct option.'}
                                {data.type === 'true_false' && 'Choose whether the statement is true or false.'}
                                {data.type === 'short_answer' && 'Recruiters type a free-text answer. A reviewer scores it manually; it is never marked automatically.'}
                            </CardDescription>
                        </CardHeader>
                        {usesOptions && (
                            <CardContent className="space-y-2">
                                {data.options.map((option, index) => (
                                    <div key={index} className="flex items-center gap-2">
                                        <span className="text-muted-foreground w-5 text-sm font-medium">{letter(index)}.</span>
                                        <Input
                                            value={option.text}
                                            disabled={fixedOptions}
                                            onChange={(event) => updateOption(index, { text: event.target.value })}
                                            placeholder={`Option ${letter(index)}`}
                                            aria-label={`Option ${letter(index)}`}
                                        />
                                        <label className="flex shrink-0 items-center gap-1.5 text-sm">
                                            <Checkbox checked={option.is_correct} onCheckedChange={(checked) => updateOption(index, { is_correct: checked === true })} />
                                            Correct
                                        </label>
                                        {!fixedOptions && (
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                aria-label={`Remove option ${letter(index)}`}
                                                disabled={data.options.length <= 2}
                                                onClick={() => setData('options', data.options.filter((_, i) => i !== index))}
                                            >
                                                <X />
                                            </Button>
                                        )}
                                    </div>
                                ))}
                                {Object.entries(errorBag)
                                    .filter(([key]) => key.startsWith('options.'))
                                    .map(([key, message]) => (
                                        <InputError key={key} message={message} />
                                    ))}
                                <InputError message={errorBag.options} />
                                {!fixedOptions && data.options.length < MAX_OPTIONS && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setData('options', [...data.options, { text: '', is_correct: false }])}
                                    >
                                        <Plus /> Add option
                                    </Button>
                                )}
                            </CardContent>
                        )}
                    </Card>

                    <Card>
                        <CardContent className="grid gap-2 pt-6">
                            <Label htmlFor="explanation">Explanation</Label>
                            <Textarea
                                id="explanation"
                                rows={3}
                                value={data.explanation}
                                onChange={(event) => setData('explanation', event.target.value)}
                                placeholder="Optional. Shown to recruiters after they submit, only if the quiz allows answer review."
                            />
                            <InputError message={errors.explanation} />
                        </CardContent>
                    </Card>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Save
                        </Button>
                        {!editing && (
                            <Button type="button" variant="outline" disabled={processing} onClick={() => save(true)()}>
                                Save and add another
                            </Button>
                        )}
                        <Button type="button" variant="ghost" asChild>
                            <Link href={backUrl}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </RecruiterLayout>
    );
}
