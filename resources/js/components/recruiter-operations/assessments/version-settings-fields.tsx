import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

export type VersionSettings = {
    instructions: string;
    passing_percentage: number | '';
    time_limit_minutes: number | '';
    max_attempts: number | '';
    randomize_questions: boolean;
    randomize_options: boolean;
    show_result: boolean;
    allow_review: boolean;
};

type SettingsErrors = Partial<Record<keyof VersionSettings, string>>;

const toggles: { key: 'randomize_questions' | 'randomize_options' | 'show_result' | 'allow_review'; label: string; hint: string }[] = [
    { key: 'randomize_questions', label: 'Shuffle questions', hint: 'Each attempt gets its own question order.' },
    { key: 'randomize_options', label: 'Shuffle options', hint: 'True/false options always stay True, False.' },
    { key: 'show_result', label: 'Show result', hint: 'Recruiters see their score and pass/fail after submitting.' },
    { key: 'allow_review', label: 'Allow answer review', hint: 'After submitting, recruiters see correct answers and explanations.' },
];

export function VersionSettingsFields({
    data,
    errors,
    onChange,
    disabled = false,
}: {
    data: VersionSettings;
    errors: SettingsErrors;
    onChange: <K extends keyof VersionSettings>(key: K, value: VersionSettings[K]) => void;
    disabled?: boolean;
}) {
    const numberValue = (value: string): number | '' => (value === '' ? '' : Number(value));

    return (
        <div className="space-y-5">
            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="passing_percentage">Pass mark (%)</Label>
                    <Input
                        id="passing_percentage"
                        type="number"
                        min={1}
                        max={100}
                        value={data.passing_percentage}
                        disabled={disabled}
                        onChange={(event) => onChange('passing_percentage', numberValue(event.target.value))}
                    />
                    <InputError message={errors.passing_percentage} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="max_attempts">Maximum attempts</Label>
                    <Input
                        id="max_attempts"
                        type="number"
                        min={1}
                        max={10}
                        value={data.max_attempts}
                        disabled={disabled}
                        onChange={(event) => onChange('max_attempts', numberValue(event.target.value))}
                    />
                    <InputError message={errors.max_attempts} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="time_limit_minutes">Time limit (minutes)</Label>
                    <Input
                        id="time_limit_minutes"
                        type="number"
                        min={1}
                        max={480}
                        placeholder="No limit"
                        value={data.time_limit_minutes}
                        disabled={disabled}
                        onChange={(event) => onChange('time_limit_minutes', numberValue(event.target.value))}
                    />
                    <InputError message={errors.time_limit_minutes} />
                </div>
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
                {toggles.map((toggle) => (
                    <label key={toggle.key} className="flex items-start gap-3 rounded-lg border p-3">
                        <Switch checked={data[toggle.key]} disabled={disabled} onCheckedChange={(checked) => onChange(toggle.key, checked)} />
                        <span>
                            <span className="block text-sm font-medium">{toggle.label}</span>
                            <span className="text-muted-foreground block text-xs">{toggle.hint}</span>
                        </span>
                    </label>
                ))}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="instructions">Instructions</Label>
                <Textarea
                    id="instructions"
                    rows={3}
                    value={data.instructions}
                    disabled={disabled}
                    onChange={(event) => onChange('instructions', event.target.value)}
                    placeholder="Shown to recruiters before and during the quiz."
                />
                <InputError message={errors.instructions} />
            </div>
        </div>
    );
}
