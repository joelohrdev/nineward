import {
    closestCenter,
    DndContext,
    pointerWithin,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    useDraggable,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type {
    CollisionDetection,
    DragEndEvent,
    DragStartEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    GripVertical,
    Lock,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { FormFieldInput } from '@/components/forms/form-field-input';
import { collectsAnswer, newFieldId } from '@/components/forms/schema';
import type {
    FieldMappingOption,
    FieldType,
    FormField,
} from '@/components/forms/schema';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/scorebook/page-header';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import { destroy, index, update } from '@/routes/forms';
import { update as saveDraft } from '@/routes/forms/draft';
import {
    destroy as closeForm,
    store as publishForm,
} from '@/routes/forms/publication';

type Props = {
    form: {
        id: number;
        title: string;
        slug: string;
        seasonId: number | null;
        seasonName: string | null;
        status: 'draft' | 'published' | 'closed';
        statusLabel: string;
        publishedVersion: number | null;
        hasUnpublishedChanges: boolean;
        publicUrl: string | null;
        priceCents: number;
        canAcceptPayments: boolean;
    };
    fields: FormField[];
    fieldTypes: { value: FieldType; label: string }[];
    mappings: FieldMappingOption[];
    seasonOptions: { id: number; name: string }[];
};

/**
 * Prefer the question under the pointer. Over empty canvas space, the canvas
 * itself wins, so dropping below the last question adds it at the end.
 */
const collisionDetection: CollisionDetection = (args) => {
    const within = pointerWithin(args);
    const question = within.find((collision) => collision.id !== 'canvas');

    if (question !== undefined) {
        return [question];
    }

    return within.length > 0 ? within : closestCenter(args);
};

type PaletteItem =
    | { kind: 'type'; type: FieldType; label: string }
    | { kind: 'mapping'; mapping: FieldMappingOption };

const paletteId = (item: PaletteItem) =>
    item.kind === 'type'
        ? `palette:type:${item.type}`
        : `palette:mapping:${item.mapping.value}`;

function fieldFromPalette(item: PaletteItem): FormField {
    if (item.kind === 'mapping') {
        return {
            id: newFieldId(),
            type: item.mapping.type,
            label: item.mapping.label,
            help: null,
            required: false,
            options: item.mapping.options,
            maps_to: item.mapping.value,
        };
    }

    return {
        id: newFieldId(),
        type: item.type,
        label: item.type === 'section' ? 'New section' : 'New question',
        help: null,
        required: false,
        options: item.type === 'select' ? ['Option 1', 'Option 2'] : [],
        maps_to: null,
    };
}

function PaletteButton({
    item,
    onAdd,
}: {
    item: PaletteItem;
    onAdd: () => void;
}) {
    const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
        id: paletteId(item),
        data: { item },
    });
    const label = item.kind === 'type' ? item.label : item.mapping.label;

    return (
        <li
            ref={setNodeRef}
            className={cn('min-w-0', isDragging && 'opacity-40')}
        >
            <div className="flex items-stretch border border-rule bg-sheet">
                <button
                    type="button"
                    className="flex cursor-grab touch-none items-center px-2 text-ink-muted"
                    aria-label={`Drag ${label} onto the form`}
                    {...listeners}
                    {...attributes}
                >
                    <GripVertical className="size-4" />
                </button>
                <button
                    type="button"
                    onClick={onAdd}
                    className="flex min-h-10 min-w-0 flex-1 items-center gap-2 py-1.5 pr-2 text-left text-sm hover:bg-wash"
                >
                    <Plus
                        className="size-3.5 shrink-0 text-ink-muted"
                        aria-hidden="true"
                    />
                    <span className="min-w-0 break-words">{label}</span>
                </button>
            </div>
        </li>
    );
}

function FieldCard({
    field,
    typeLabel,
    isLocked,
    isSelected,
    error,
    onSelect,
}: {
    field: FormField;
    typeLabel: string;
    isLocked: boolean;
    isSelected: boolean;
    error?: string;
    onSelect: () => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: field.id,
    });

    return (
        <li
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'flex items-stretch border-2 bg-sheet',
                isSelected ? 'border-brand' : 'border-rule',
                isDragging && 'z-10 opacity-60',
                field.type === 'section' && 'border-ink',
            )}
        >
            <button
                type="button"
                className="flex cursor-grab touch-none items-center px-2 text-ink-muted"
                aria-label={`Reorder ${field.label}`}
                {...listeners}
                {...attributes}
            >
                <GripVertical className="size-5" />
            </button>
            <button
                type="button"
                onClick={onSelect}
                aria-pressed={isSelected}
                className="min-w-0 flex-1 py-3 pr-3 text-left"
            >
                <span className="flex items-center gap-2">
                    <span
                        className={cn(
                            'min-w-0 truncate font-semibold',
                            field.type === 'section' &&
                                'font-display text-xl uppercase',
                        )}
                    >
                        {field.label || 'Untitled question'}
                    </span>
                    {field.required && (
                        <span className="text-danger" aria-label="Required">
                            *
                        </span>
                    )}
                    {isLocked && (
                        <Lock
                            className="size-3.5 shrink-0 text-ink-muted"
                            aria-label="Always on the form"
                        />
                    )}
                </span>
                <span className="mt-1 block label-caps text-[0.6875rem] text-ink-muted">
                    {typeLabel}
                    {field.maps_to && ' · Saved to the player record'}
                </span>
                <InputError message={error} className="mt-1" />
            </button>
        </li>
    );
}

export default function FormBuilder({
    form,
    fields: savedFields,
    fieldTypes,
    mappings,
    seasonOptions,
}: Props) {
    const { errors } = usePage().props;
    const [fields, setFields] = useState<FormField[]>(savedFields);
    const [selectedId, setSelectedId] = useState<string | null>(
        savedFields[0]?.id ?? null,
    );
    const [isDirty, setIsDirty] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [mode, setMode] = useState<'build' | 'preview'>('build');
    const [dragging, setDragging] = useState<string | null>(null);
    const [isPaletteOpen, setIsPaletteOpen] = useState(false);

    const coreMappings = useMemo(
        () =>
            new Set(
                mappings
                    .filter((mapping) => mapping.isCore)
                    .map((mapping) => mapping.value),
            ),
        [mappings],
    );
    const isLocked = (field: FormField) =>
        field.maps_to !== null && coreMappings.has(field.maps_to);

    const typeLabels = useMemo(
        () =>
            Object.fromEntries(
                fieldTypes.map((type) => [type.value, type.label]),
            ) as Record<FieldType, string>,
        [fieldTypes],
    );

    useEffect(() => {
        if (!isDirty) {
            return;
        }

        const warn = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', warn);

        return () => window.removeEventListener('beforeunload', warn);
    }, [isDirty]);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const usedMappings = new Set(
        fields.map((field) => field.maps_to).filter(Boolean),
    );
    const palette: { title: string; items: PaletteItem[] }[] = [
        {
            title: 'Questions',
            items: fieldTypes.map((type) => ({
                kind: 'type' as const,
                type: type.value,
                label: type.label,
            })),
        },
        {
            title: 'Player and guardian details',
            items: mappings
                .filter(
                    (mapping) =>
                        !mapping.isCore && !usedMappings.has(mapping.value),
                )
                .map((mapping) => ({ kind: 'mapping' as const, mapping })),
        },
    ];

    const change = (next: FormField[]) => {
        setFields(next);
        setIsDirty(true);
    };

    const insert = (item: PaletteItem, atIndex?: number) => {
        const field = fieldFromPalette(item);
        const selectedIndex = fields.findIndex(
            (existing) => existing.id === selectedId,
        );
        const position =
            atIndex ??
            (selectedIndex === -1 ? fields.length : selectedIndex + 1);
        const next = [...fields];
        next.splice(position, 0, field);
        change(next);
        setSelectedId(field.id);
        setMode('build');
        setIsPaletteOpen(false);
    };

    const updateField = (id: string, changes: Partial<FormField>) =>
        change(
            fields.map((field) =>
                field.id === id ? { ...field, ...changes } : field,
            ),
        );

    const move = (id: string, offset: -1 | 1) => {
        const from = fields.findIndex((field) => field.id === id);
        const to = from + offset;

        if (to >= 0 && to < fields.length) {
            change(arrayMove(fields, from, to));
        }
    };

    const remove = (id: string) => {
        const position = fields.findIndex((field) => field.id === id);
        const next = fields.filter((field) => field.id !== id);
        change(next);
        setSelectedId(next[Math.min(position, next.length - 1)]?.id ?? null);
    };

    const onDragStart = (event: DragStartEvent) =>
        setDragging(String(event.active.id));

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        setDragging(null);

        if (over === null) {
            return;
        }

        const item = active.data.current?.item as PaletteItem | undefined;

        if (item !== undefined) {
            const overIndex = fields.findIndex((field) => field.id === over.id);
            insert(item, overIndex === -1 ? fields.length : overIndex);

            return;
        }

        if (active.id !== over.id) {
            const from = fields.findIndex((field) => field.id === active.id);
            const to = fields.findIndex((field) => field.id === over.id);

            if (from !== -1 && to !== -1) {
                change(arrayMove(fields, from, to));
            }
        }
    };

    const save = () => {
        setIsSaving(true);
        router.put(
            saveDraft.url(form.id),
            { fields },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setIsDirty(false),
                onFinish: () => setIsSaving(false),
            },
        );
    };

    const fieldError = (fieldIndex: number) =>
        Object.entries(errors).find(([key]) =>
            key.startsWith(`fields.${fieldIndex}.`),
        )?.[1];

    const selected = fields.find((field) => field.id === selectedId) ?? null;
    const selectedIndex = selected === null ? -1 : fields.indexOf(selected);
    const draggedPaletteItem = dragging?.startsWith('palette:')
        ? palette
              .flatMap((group) => group.items)
              .find((item) => paletteId(item) === dragging)
        : undefined;
    const draggedField = fields.find((field) => field.id === dragging);

    const statusTone =
        form.status === 'published'
            ? 'done'
            : form.status === 'closed'
              ? 'neutral'
              : 'open';

    return (
        <>
            <Head title={`${form.title} · Form builder`} />

            <PageHeader
                title={form.title}
                context={
                    <span className="inline-flex flex-wrap items-center gap-x-3 gap-y-1">
                        <StatusMark tone={statusTone}>
                            {form.statusLabel}
                        </StatusMark>
                        {form.publishedVersion !== null && (
                            <span>
                                Published version {form.publishedVersion}
                            </span>
                        )}
                        {form.seasonName && <span>For {form.seasonName}</span>}
                        {form.priceCents > 0 && (
                            <span>{formatMoney(form.priceCents)} fee</span>
                        )}
                        {form.hasUnpublishedChanges &&
                            form.publishedVersion !== null && (
                                <span className="text-ink">
                                    Unpublished changes
                                </span>
                            )}
                    </span>
                }
                actions={
                    <>
                        <FormDialog
                            title="Form details"
                            trigger={
                                <Button variant="outline">
                                    <Pencil />
                                    Details
                                </Button>
                            }
                            form={update.form(form.id)}
                            submitLabel="Save details"
                        >
                            {(detailErrors) => (
                                <>
                                    <Field
                                        id="form-title"
                                        label="Title"
                                        error={detailErrors.title}
                                    >
                                        <Input
                                            id="form-title"
                                            name="title"
                                            defaultValue={form.title}
                                            required
                                        />
                                    </Field>
                                    <Field
                                        id="form-price"
                                        label="Registration fee"
                                        hint={
                                            form.canAcceptPayments
                                                ? 'In dollars. Leave at 0 for a free registration.'
                                                : 'Connect Stripe in Organization → Payments before charging a fee.'
                                        }
                                        error={detailErrors.price}
                                    >
                                        <Input
                                            id="form-price"
                                            name="price"
                                            type="number"
                                            inputMode="decimal"
                                            min={0}
                                            max={5000}
                                            step="0.01"
                                            defaultValue={(
                                                form.priceCents / 100
                                            ).toFixed(2)}
                                        />
                                    </Field>
                                    <Field
                                        id="form-season"
                                        label="Season"
                                        hint="Registrations from this form are for this season."
                                        error={detailErrors.season_id}
                                    >
                                        <NativeSelect
                                            id="form-season"
                                            name="season_id"
                                            defaultValue={form.seasonId ?? ''}
                                        >
                                            <option value="">
                                                No particular season
                                            </option>
                                            {seasonOptions.map((season) => (
                                                <option
                                                    key={season.id}
                                                    value={season.id}
                                                >
                                                    {season.name}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </Field>
                                </>
                            )}
                        </FormDialog>
                        {form.status === 'published' ? (
                            <ConfirmDialog
                                title={`Close ${form.title}?`}
                                description="Families won't be able to submit it until you publish it again."
                                confirmLabel="Close form"
                                trigger={
                                    <Button variant="outline">Close</Button>
                                }
                                form={closeForm.form(form.id)}
                            />
                        ) : null}
                        {(form.hasUnpublishedChanges ||
                            form.status !== 'published') && (
                            <Button asChild disabled={isDirty}>
                                <Link
                                    href={publishForm(form.id)}
                                    as="button"
                                    preserveScroll
                                    disabled={isDirty}
                                    title={
                                        isDirty
                                            ? 'Save your changes first'
                                            : undefined
                                    }
                                >
                                    {form.publishedVersion === null
                                        ? 'Publish'
                                        : form.hasUnpublishedChanges
                                          ? 'Publish changes'
                                          : 'Reopen'}
                                </Link>
                            </Button>
                        )}
                        {form.publishedVersion === null && (
                            <ConfirmDialog
                                title={`Delete ${form.title}?`}
                                description="This form has never been published, so nothing else is affected."
                                confirmLabel="Delete form"
                                trigger={
                                    <Button
                                        variant="ghost"
                                        aria-label="Delete form"
                                    >
                                        <Trash2 />
                                    </Button>
                                }
                                form={destroy.form(form.id)}
                            />
                        )}
                    </>
                }
            />

            <InputError message={errors.form} className="mb-4" />

            {form.publicUrl !== null && (
                <div className="mb-6 flex flex-wrap items-center gap-3 border-l-4 border-grass bg-sheet px-3 py-2 text-sm">
                    <span className="label-caps text-ink-muted">
                        Link for families
                    </span>
                    <a
                        href={form.publicUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="min-w-0 truncate font-mono underline underline-offset-4"
                    >
                        {form.publicUrl}
                    </a>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() =>
                            void navigator.clipboard.writeText(
                                form.publicUrl ?? '',
                            )
                        }
                    >
                        Copy
                    </Button>
                </div>
            )}

            <div className="sticky top-0 z-20 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-rule bg-paper/95 px-4 py-3 backdrop-blur md:-mx-8 md:px-8">
                <div
                    role="tablist"
                    aria-label="Builder mode"
                    className="inline-flex border-2 border-ink"
                >
                    {(['build', 'preview'] as const).map((tab) => (
                        <button
                            key={tab}
                            type="button"
                            role="tab"
                            aria-selected={mode === tab}
                            onClick={() => setMode(tab)}
                            className={cn(
                                'flex h-10 items-center gap-2 px-4 label-caps',
                                mode === tab ? 'bg-ink text-paper' : 'text-ink',
                            )}
                        >
                            {tab === 'preview' && (
                                <Eye className="size-4" aria-hidden="true" />
                            )}
                            {tab === 'build' ? 'Build' : 'Preview'}
                        </button>
                    ))}
                </div>
                <div className="flex items-center gap-3">
                    <span className="text-sm text-ink-muted" aria-live="polite">
                        {isSaving
                            ? 'Saving…'
                            : isDirty
                              ? 'Unsaved changes'
                              : 'All changes saved'}
                    </span>
                    <Button onClick={save} disabled={!isDirty || isSaving}>
                        {isSaving && <Spinner />}
                        Save draft
                    </Button>
                </div>
            </div>

            {errors.fields && (
                <p className="mb-4 border-l-4 border-danger bg-sheet px-3 py-2 text-sm font-medium text-danger">
                    {errors.fields}
                </p>
            )}

            {mode === 'preview' ? (
                <div className="mx-auto max-w-xl border-2 border-ink bg-sheet p-6">
                    <h2 className="mb-6 font-display text-3xl uppercase">
                        {form.title}
                    </h2>
                    <div className="grid gap-6">
                        {fields.map((field) => (
                            <FormFieldInput
                                key={field.id}
                                field={field}
                                disabled
                            />
                        ))}
                    </div>
                </div>
            ) : (
                <DndContext
                    sensors={sensors}
                    collisionDetection={collisionDetection}
                    onDragStart={onDragStart}
                    onDragEnd={onDragEnd}
                    onDragCancel={() => setDragging(null)}
                >
                    <div
                        className={cn(
                            'grid gap-6 lg:grid-cols-[13rem_minmax(0,1fr)_20rem]',
                            dragging !== null && 'select-none',
                        )}
                    >
                        <Button
                            variant="secondary"
                            className="lg:hidden"
                            aria-expanded={isPaletteOpen}
                            onClick={() => setIsPaletteOpen((open) => !open)}
                        >
                            <Plus />
                            {isPaletteOpen
                                ? 'Hide question types'
                                : 'Add a question'}
                        </Button>
                        <aside
                            className={cn(
                                'min-w-0 space-y-5 lg:block',
                                isPaletteOpen ? 'block' : 'hidden',
                            )}
                            aria-label="Add questions"
                        >
                            {palette.map((group) =>
                                group.items.length === 0 ? null : (
                                    <section key={group.title}>
                                        <h2 className="mb-2 label-caps text-ink-muted">
                                            {group.title}
                                        </h2>
                                        <ul className="grid grid-cols-[minmax(0,1fr)] gap-1">
                                            {group.items.map((item) => (
                                                <PaletteButton
                                                    key={paletteId(item)}
                                                    item={item}
                                                    onAdd={() => insert(item)}
                                                />
                                            ))}
                                        </ul>
                                    </section>
                                ),
                            )}
                        </aside>

                        <Canvas>
                            <SortableContext
                                items={fields.map((field) => field.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                <ol className="grid gap-2">
                                    {fields.map((field, fieldIndex) => (
                                        <FieldCard
                                            key={field.id}
                                            field={field}
                                            typeLabel={typeLabels[field.type]}
                                            isLocked={isLocked(field)}
                                            isSelected={field.id === selectedId}
                                            error={fieldError(fieldIndex)}
                                            onSelect={() =>
                                                setSelectedId(field.id)
                                            }
                                        />
                                    ))}
                                </ol>
                            </SortableContext>
                        </Canvas>

                        <aside
                            aria-label="Question settings"
                            className="lg:sticky lg:top-20 lg:self-start"
                        >
                            {selected === null ? (
                                <p className="border-2 border-dashed border-rule p-4 text-sm text-ink-muted">
                                    Select a question to edit it.
                                </p>
                            ) : (
                                <FieldSettings
                                    key={selected.id}
                                    field={selected}
                                    typeLabel={typeLabels[selected.type]}
                                    locked={isLocked(selected)}
                                    canMoveUp={selectedIndex > 0}
                                    canMoveDown={
                                        selectedIndex < fields.length - 1
                                    }
                                    onChange={(changes) =>
                                        updateField(selected.id, changes)
                                    }
                                    onMove={(offset) =>
                                        move(selected.id, offset)
                                    }
                                    onRemove={() => remove(selected.id)}
                                />
                            )}
                        </aside>
                    </div>

                    <DragOverlay>
                        {draggedPaletteItem && (
                            <div className="border-2 border-brand bg-sheet px-3 py-2 text-sm font-semibold shadow-lg">
                                {draggedPaletteItem.kind === 'type'
                                    ? draggedPaletteItem.label
                                    : draggedPaletteItem.mapping.label}
                            </div>
                        )}
                        {draggedField && (
                            <div className="border-2 border-brand bg-sheet px-3 py-3 font-semibold shadow-lg">
                                {draggedField.label}
                            </div>
                        )}
                    </DragOverlay>
                </DndContext>
            )}

            <p className="mt-8 text-sm text-ink-muted">
                <Link href={index()} className="underline underline-offset-4">
                    All registration forms
                </Link>
            </p>
        </>
    );
}

function Canvas({ children }: { children: React.ReactNode }) {
    const { setNodeRef, isOver } = useDroppable({ id: 'canvas' });

    return (
        <section
            ref={setNodeRef}
            aria-label="Form questions"
            className={cn(
                'min-h-40 border-2 border-dashed p-2 pb-16 transition-colors',
                isOver ? 'border-brand bg-wash/60' : 'border-transparent',
            )}
        >
            {children}
        </section>
    );
}

function FieldSettings({
    field,
    typeLabel,
    locked,
    canMoveUp,
    canMoveDown,
    onChange,
    onMove,
    onRemove,
}: {
    field: FormField;
    typeLabel: string;
    locked: boolean;
    canMoveUp: boolean;
    canMoveDown: boolean;
    onChange: (changes: Partial<FormField>) => void;
    onMove: (offset: -1 | 1) => void;
    onRemove: () => void;
}) {
    const isMapped = field.maps_to !== null;

    return (
        <div className="grid gap-5 border-2 border-ink bg-sheet p-4">
            <div className="flex items-center justify-between gap-2 border-b border-ink pb-2">
                <h2 className="label-caps text-ink">{typeLabel}</h2>
                <span className="flex gap-1">
                    <Button
                        size="sm"
                        variant="ghost"
                        disabled={!canMoveUp}
                        onClick={() => onMove(-1)}
                        aria-label="Move up"
                    >
                        <ArrowUp />
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        disabled={!canMoveDown}
                        onClick={() => onMove(1)}
                        aria-label="Move down"
                    >
                        <ArrowDown />
                    </Button>
                </span>
            </div>

            <Field
                id="setting-label"
                label={field.type === 'section' ? 'Heading' : 'Question'}
            >
                <Input
                    id="setting-label"
                    value={field.label}
                    onChange={(event) =>
                        onChange({ label: event.target.value })
                    }
                />
            </Field>

            <Field
                id="setting-help"
                label={field.type === 'section' ? 'Note' : 'Hint'}
            >
                <Input
                    id="setting-help"
                    value={field.help ?? ''}
                    onChange={(event) =>
                        onChange({ help: event.target.value || null })
                    }
                    placeholder="Optional"
                />
            </Field>

            {collectsAnswer(field.type) && (
                <div className="flex items-center gap-3">
                    <Checkbox
                        id="setting-required"
                        checked={field.required}
                        disabled={locked}
                        onCheckedChange={(checked) =>
                            onChange({ required: checked === true })
                        }
                    />
                    <Label htmlFor="setting-required">Required</Label>
                </div>
            )}

            {field.type === 'select' && !isMapped && (
                <Field
                    id="setting-options"
                    label="Choices"
                    hint="One per line."
                >
                    <textarea
                        id="setting-options"
                        rows={5}
                        value={field.options.join('\n')}
                        onChange={(event) =>
                            onChange({
                                options: event.target.value.split('\n'),
                            })
                        }
                        onBlur={() =>
                            onChange({
                                options: field.options
                                    .map((option) => option.trim())
                                    .filter(Boolean),
                            })
                        }
                        className="w-full rounded-sm border border-b-2 border-rule border-b-rule-strong bg-paper px-3 py-2 text-base text-ink outline-none focus-visible:border-b-clay focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay"
                    />
                </Field>
            )}

            {isMapped && (
                <p className="border-l-4 border-rule-strong bg-paper px-3 py-2 text-sm text-ink-muted">
                    {locked
                        ? 'Every registration form asks this, and the answer fills in the player or guardian record. You can reword it, but not remove it.'
                        : 'The answer fills in the player or guardian record.'}
                    {field.options.length > 0 &&
                        ` Choices: ${field.options.join(', ')}.`}
                </p>
            )}

            {!locked && (
                <Button
                    variant="ghost"
                    className="justify-start text-danger"
                    onClick={onRemove}
                >
                    <Trash2 />
                    Remove question
                </Button>
            )}
        </div>
    );
}
