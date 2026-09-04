import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Eraser, PenLine, Type, Upload } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

export type SignatureValue = {
    signature_type: 'drawn' | 'uploaded' | 'typed' | '';
    signature_data: string;
};

type SignatureMode = 'drawn' | 'uploaded' | 'typed';

interface SignatureCaptureProps {
    value: SignatureValue;
    onChange: (value: SignatureValue) => void;
    className?: string;
}

const MODES: { key: SignatureMode; label: string; icon: typeof PenLine }[] = [
    { key: 'drawn', label: 'Draw', icon: PenLine },
    { key: 'uploaded', label: 'Upload', icon: Upload },
    { key: 'typed', label: 'Type', icon: Type },
];

export function SignatureCapture({ value, onChange, className }: SignatureCaptureProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const drawing = useRef(false);
    const [mode, setMode] = useState<SignatureMode>(
        value.signature_type === 'drawn' || value.signature_type === 'uploaded' || value.signature_type === 'typed'
            ? value.signature_type
            : 'drawn',
    );
    const [typedName, setTypedName] = useState(value.signature_type === 'typed' ? value.signature_data : '');

    const syncCanvasSize = useCallback(() => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return;
        }

        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111827';
    }, []);

    useEffect(() => {
        syncCanvasSize();
        window.addEventListener('resize', syncCanvasSize);

        return () => window.removeEventListener('resize', syncCanvasSize);
    }, [syncCanvasSize]);

    const point = (event: React.PointerEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return { x: 0, y: 0 };
        }

        const rect = canvas.getBoundingClientRect();

        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top,
        };
    };

    const startDraw = (event: React.PointerEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) {
            return;
        }

        drawing.current = true;
        canvas.setPointerCapture(event.pointerId);
        const { x, y } = point(event);
        ctx.beginPath();
        ctx.moveTo(x, y);
    };

    const draw = (event: React.PointerEvent<HTMLCanvasElement>) => {
        if (!drawing.current) {
            return;
        }

        const ctx = canvasRef.current?.getContext('2d');
        if (!ctx) {
            return;
        }

        const { x, y } = point(event);
        ctx.lineTo(x, y);
        ctx.stroke();
    };

    const endDraw = (event: React.PointerEvent<HTMLCanvasElement>) => {
        if (!drawing.current) {
            return;
        }

        drawing.current = false;
        canvasRef.current?.releasePointerCapture(event.pointerId);

        const canvas = canvasRef.current;
        if (canvas) {
            onChange({ signature_type: 'drawn', signature_data: canvas.toDataURL('image/png') });
        }
    };

    const clearCanvas = () => {
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) {
            return;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        onChange({ signature_type: 'drawn', signature_data: '' });
    };

    const handleUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            const result = typeof reader.result === 'string' ? reader.result : '';
            onChange({ signature_type: 'uploaded', signature_data: result });
        };
        reader.readAsDataURL(file);
    };

    const switchMode = (next: SignatureMode) => {
        setMode(next);
        onChange({ signature_type: '', signature_data: '' });
        setTypedName('');
    };

    return (
        <div className={cn('space-y-4', className)}>
            <div className="flex flex-wrap gap-2">
                {MODES.map(({ key, label, icon: Icon }) => (
                    <Button key={key} type="button" size="sm" variant={mode === key ? 'default' : 'outline'} onClick={() => switchMode(key)}>
                        <Icon className="size-4" />
                        {label}
                    </Button>
                ))}
            </div>

            {mode === 'drawn' && (
                <div className="space-y-2">
                    <Label>Draw your signature</Label>
                    <div className="relative overflow-hidden rounded-lg border bg-white">
                        <canvas
                            ref={canvasRef}
                            className="h-36 w-full touch-none"
                            onPointerDown={startDraw}
                            onPointerMove={draw}
                            onPointerUp={endDraw}
                            onPointerLeave={endDraw}
                        />
                        {value.signature_type === 'drawn' && value.signature_data && (
                            <img src={value.signature_data} alt="Signature preview" className="pointer-events-none absolute inset-0 hidden" />
                        )}
                    </div>
                    <Button type="button" variant="outline" size="sm" onClick={clearCanvas}>
                        <Eraser className="size-4" />
                        Clear
                    </Button>
                </div>
            )}

            {mode === 'uploaded' && (
                <div className="space-y-2">
                    <Label htmlFor="signature-upload">Upload signature image</Label>
                    <Input id="signature-upload" type="file" accept="image/png,image/jpeg,image/webp" onChange={handleUpload} />
                    {value.signature_type === 'uploaded' && value.signature_data && (
                        <img src={value.signature_data} alt="Uploaded signature" className="max-h-24 rounded border bg-white p-2" />
                    )}
                </div>
            )}

            {mode === 'typed' && (
                <div className="space-y-2">
                    <Label htmlFor="typed-signature">Type your full name</Label>
                    <Input
                        id="typed-signature"
                        value={typedName}
                        onChange={(event) => {
                            const next = event.target.value;
                            setTypedName(next);
                            onChange({ signature_type: 'typed', signature_data: next });
                        }}
                        placeholder="Jane Doe"
                        className="font-serif text-lg italic"
                    />
                    {typedName && (
                        <div className="rounded-lg border bg-white px-4 py-6 text-center font-serif text-2xl italic">{typedName}</div>
                    )}
                </div>
            )}
        </div>
    );
}
