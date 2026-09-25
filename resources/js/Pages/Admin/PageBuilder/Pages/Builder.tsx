import { Head, Link, router } from '@inertiajs/react'
import { useEffect, useRef, useState } from 'react'
import type { ReactNode } from 'react'

import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    CatalogCategoryOption,
    CatalogProductOption,
    CatalogSourceDefinition,
    PageData,
    PageSection,
    SectionDefinition,
} from '@/types/page-builder'

import EditSectionDialog from './Partials/EditSectionDialog'
import SectionLibraryDialog from './Partials/SectionLibraryDialog'

import SectionPreviewRenderer from '../Sections/Previews/SectionPreviewRenderer'

interface Props {
    page: PageData
    sectionDefinitions: SectionDefinition[]
    catalogSources: CatalogSourceDefinition[]
    categoryOptions: CatalogCategoryOption[]
    selectedProductOptions: CatalogProductOption[]
}

export default function Builder({
    page,
    sectionDefinitions,
    catalogSources,
    categoryOptions,
    selectedProductOptions,
}: Props) {
    const [libraryOpen, setLibraryOpen] = useState(false)

    const [sectionToEdit, setSectionToEdit] = useState<PageSection | null>(null)

    const [openMenuId, setOpenMenuId] = useState<number | null>(null)

    const [sectionToDelete, setSectionToDelete] = useState<PageSection | null>(null)

    const [duplicatingSectionId, setDuplicatingSectionId] = useState<number | null>(null)

    const [deletingSectionId, setDeletingSectionId] = useState<number | null>(null)

    const [reorderingSectionId, setReorderingSectionId] = useState<number | null>(null)

    const canAddSections = sectionDefinitions.length > 0

    const mutating =
        duplicatingSectionId !== null || deletingSectionId !== null || reorderingSectionId !== null

    const closeActionMenu = () => {
        setOpenMenuId(null)
    }

    const duplicateSection = (section: PageSection) => {
        if (mutating) {
            return
        }

        closeActionMenu()
        setDuplicatingSectionId(section.id)

        router.post(
            route('admin.pages.sections.duplicate', [page.id, section.id]),
            {},
            {
                preserveScroll: true,

                onFinish: () => {
                    setDuplicatingSectionId(null)
                },
            },
        )
    }

    const moveSection = (sectionIndex: number, direction: 'up' | 'down') => {
        if (mutating) {
            return
        }

        const targetIndex = direction === 'up' ? sectionIndex - 1 : sectionIndex + 1

        if (targetIndex < 0 || targetIndex >= page.sections.length) {
            return
        }

        const movingSection = page.sections[sectionIndex]

        if (!movingSection) {
            return
        }

        const reorderedSections = [...page.sections]

        ;[reorderedSections[sectionIndex], reorderedSections[targetIndex]] = [
            reorderedSections[targetIndex],
            reorderedSections[sectionIndex],
        ]

        closeActionMenu()

        setReorderingSectionId(movingSection.id)

        router.put(
            route('admin.pages.sections.reorder', page.id),
            {
                section_ids: reorderedSections.map((section) => section.id),
            },
            {
                preserveScroll: true,

                onFinish: () => {
                    setReorderingSectionId(null)
                },
            },
        )
    }

    const requestDelete = (section: PageSection) => {
        if (mutating) {
            return
        }

        closeActionMenu()
        setSectionToDelete(section)
    }

    const deleteSection = () => {
        if (sectionToDelete === null || mutating) {
            return
        }

        const sectionId = sectionToDelete.id

        setDeletingSectionId(sectionId)

        router.delete(route('admin.pages.sections.destroy', [page.id, sectionId]), {
            preserveScroll: true,

            onSuccess: () => {
                setSectionToDelete(null)
            },

            onFinish: () => {
                setDeletingSectionId(null)
            },
        })
    }

    const closeDeleteDialog = () => {
        if (deletingSectionId !== null) {
            return
        }

        setSectionToDelete(null)
    }

    const openEditor = (section: PageSection) => {
        if (mutating) {
            return
        }

        closeActionMenu()
        setSectionToEdit(section)
    }

    const closeEditor = () => {
        setSectionToEdit(null)
    }

    const openSectionLibrary = () => {
        if (!canAddSections || mutating) {
            return
        }

        closeActionMenu()
        setLibraryOpen(true)
    }

    const closeSectionLibrary = () => {
        setLibraryOpen(false)
    }

    return (
        <>
            <Head title={`Page Builder - ${page.title}`} />

            <AdminLayout
                title="Page Builder"
                description={`${page.title} · /${page.slug}`}
                actions={
                    <Link
                        href={route('admin.pages.index')}
                        className="text-sm font-medium text-neutral-600 transition hover:text-neutral-950 dark:text-neutral-400 dark:hover:text-neutral-100"
                    >
                        Back to Pages
                    </Link>
                }
            >
                <div className="grid items-start gap-6 lg:grid-cols-[275px_minmax(0,1fr)]">
                    <aside className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                        <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            Sections
                        </h2>

                        <p className="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Add a pre-designed section to this page.
                        </p>

                        <button
                            type="button"
                            disabled={!canAddSections || mutating}
                            onClick={openSectionLibrary}
                            className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                        >
                            <span aria-hidden="true">+</span>
                            Add Section
                        </button>

                        {page.sections.length > 0 && (
                            <div className="mt-5 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                                <p className="text-xs font-medium uppercase tracking-wide text-neutral-400">
                                    Page Structure
                                </p>

                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                    {page.sections.length}{' '}
                                    {page.sections.length === 1 ? 'section' : 'sections'}
                                </p>

                                <p className="mt-2 text-xs leading-5 text-neutral-400">
                                    Use the section actions to move sections up or down.
                                </p>
                            </div>
                        )}
                    </aside>

                    <main className="min-w-0">
                        {page.sections.length === 0 ? (
                            <div className="flex min-h-72 items-center justify-center rounded-xl border border-dashed border-neutral-300 bg-white/40 px-6 py-12 text-center dark:border-neutral-700 dark:bg-neutral-950/40">
                                <div>
                                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                        Start building your page
                                    </h2>

                                    <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        Add a pre-designed section from the Section Library.
                                    </p>

                                    <button
                                        type="button"
                                        disabled={!canAddSections || mutating}
                                        onClick={openSectionLibrary}
                                        className="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                                    >
                                        <span aria-hidden="true">+</span>
                                        Add Your First Section
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-5">
                                {page.sections.map((section, index) => (
                                    <SectionCard
                                        key={section.id}
                                        section={section}
                                        sectionIndex={index}
                                        sectionCount={page.sections.length}
                                        sectionDefinitions={sectionDefinitions}
                                        menuOpen={openMenuId === section.id}
                                        mutating={mutating}
                                        reordering={reorderingSectionId === section.id}
                                        duplicating={duplicatingSectionId === section.id}
                                        deleting={deletingSectionId === section.id}
                                        onToggleMenu={() =>
                                            setOpenMenuId((current) =>
                                                current === section.id ? null : section.id,
                                            )
                                        }
                                        onCloseMenu={closeActionMenu}
                                        onEdit={() => openEditor(section)}
                                        onDuplicate={() => duplicateSection(section)}
                                        onMoveUp={() => moveSection(index, 'up')}
                                        onMoveDown={() => moveSection(index, 'down')}
                                        onDelete={() => requestDelete(section)}
                                    />
                                ))}
                            </div>
                        )}
                    </main>
                </div>

                <SectionLibraryDialog
                    open={libraryOpen}
                    pageId={page.id}
                    sectionDefinitions={sectionDefinitions}
                    onClose={closeSectionLibrary}
                />

                {sectionToEdit !== null && (
                    <EditSectionDialog
                        key={sectionToEdit.id}
                        open
                        pageId={page.id}
                        section={sectionToEdit}
                        sectionDefinitions={sectionDefinitions}
                        catalogSources={catalogSources}
                        categoryOptions={categoryOptions}
                        selectedProductOptions={selectedProductOptions}
                        onClose={closeEditor}
                    />
                )}

                <ConfirmDialog
                    open={sectionToDelete !== null}
                    title="Delete Section?"
                    description={
                        sectionToDelete
                            ? `Are you sure you want to delete "${getSectionLabel(
                                  sectionToDelete.type,
                                  sectionDefinitions,
                              )}" from this page?`
                            : ''
                    }
                    confirmLabel="Delete Section"
                    cancelLabel="Cancel"
                    processing={deletingSectionId !== null}
                    onConfirm={deleteSection}
                    onCancel={closeDeleteDialog}
                />
            </AdminLayout>
        </>
    )
}

interface SectionCardProps {
    section: PageSection
    sectionIndex: number
    sectionCount: number
    sectionDefinitions: SectionDefinition[]
    menuOpen: boolean
    mutating: boolean
    reordering: boolean
    duplicating: boolean
    deleting: boolean
    onToggleMenu: () => void
    onCloseMenu: () => void
    onEdit: () => void
    onDuplicate: () => void
    onMoveUp: () => void
    onMoveDown: () => void
    onDelete: () => void
}

function SectionCard({
    section,
    sectionIndex,
    sectionCount,
    sectionDefinitions,
    menuOpen,
    mutating,
    reordering,
    duplicating,
    deleting,
    onToggleMenu,
    onCloseMenu,
    onEdit,
    onDuplicate,
    onMoveUp,
    onMoveDown,
    onDelete,
}: SectionCardProps) {
    const menuRef = useRef<HTMLDivElement>(null)

    const sectionLabel = getSectionLabel(section.type, sectionDefinitions)

    const templateLabel = getTemplateLabel(section.type, section.template, sectionDefinitions)

    useEffect(() => {
        if (!menuOpen) {
            return
        }

        const handlePointerDown = (event: MouseEvent) => {
            if (
                menuRef.current &&
                event.target instanceof Node &&
                !menuRef.current.contains(event.target)
            ) {
                onCloseMenu()
            }
        }

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onCloseMenu()
            }
        }

        document.addEventListener('mousedown', handlePointerDown)

        document.addEventListener('keydown', handleKeyDown)

        return () => {
            document.removeEventListener('mousedown', handlePointerDown)

            document.removeEventListener('keydown', handleKeyDown)
        }
    }, [menuOpen, onCloseMenu])

    return (
        <article
            aria-busy={reordering || duplicating || deleting}
            className={[
                'overflow-visible rounded-xl border bg-white shadow-sm transition dark:bg-neutral-950',
                section.is_enabled
                    ? 'border-neutral-200 dark:border-neutral-800'
                    : 'border-neutral-200 opacity-70 dark:border-neutral-800',
                reordering ? 'ring-2 ring-neutral-300 dark:ring-neutral-700' : '',
            ]
                .filter(Boolean)
                .join(' ')}
        >
            <div className="relative flex items-center gap-3 rounded-t-xl border-b border-neutral-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950">
                <div
                    aria-hidden="true"
                    title="Section ordering controls"
                    className="inline-flex h-9 w-8 shrink-0 items-center justify-center rounded-md text-lg text-neutral-400"
                >
                    ⠿
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            {sectionLabel}
                        </p>

                        {!section.is_enabled && (
                            <span className="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                                Disabled
                            </span>
                        )}

                        {reordering && <OperationStatus>Reordering...</OperationStatus>}

                        {duplicating && <OperationStatus>Duplicating...</OperationStatus>}

                        {deleting && <OperationStatus destructive>Deleting...</OperationStatus>}
                    </div>

                    <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                        {templateLabel}
                    </p>
                </div>

                <button
                    type="button"
                    onClick={onEdit}
                    disabled={mutating}
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-neutral-200 px-3 text-sm font-medium text-neutral-600 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-900"
                >
                    <span aria-hidden="true">✎</span>

                    <span className="hidden sm:inline">Edit</span>
                </button>

                <div ref={menuRef} className="relative">
                    <button
                        type="button"
                        onClick={onToggleMenu}
                        disabled={mutating}
                        aria-label={`Actions for ${sectionLabel}`}
                        aria-haspopup="menu"
                        aria-expanded={menuOpen}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-md text-neutral-600 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-neutral-300 dark:hover:bg-neutral-900"
                    >
                        <span aria-hidden="true">•••</span>
                    </button>

                    {menuOpen && (
                        <SectionActionsMenu
                            sectionLabel={sectionLabel}
                            canMoveUp={sectionIndex > 0}
                            canMoveDown={sectionIndex < sectionCount - 1}
                            onEdit={onEdit}
                            onDuplicate={onDuplicate}
                            onMoveUp={onMoveUp}
                            onMoveDown={onMoveDown}
                            onDelete={onDelete}
                        />
                    )}
                </div>
            </div>

            <SectionPreviewRenderer
                section={section}
                sectionLabel={sectionLabel}
                templateLabel={templateLabel}
            />
        </article>
    )
}

function OperationStatus({
    children,
    destructive = false,
}: {
    children: ReactNode
    destructive?: boolean
}) {
    return (
        <span
            role="status"
            aria-live="polite"
            className={
                destructive
                    ? 'text-xs font-medium text-red-500 dark:text-red-400'
                    : 'text-xs font-medium text-neutral-400'
            }
        >
            {children}
        </span>
    )
}

interface SectionActionsMenuProps {
    sectionLabel: string
    canMoveUp: boolean
    canMoveDown: boolean
    onEdit: () => void
    onDuplicate: () => void
    onMoveUp: () => void
    onMoveDown: () => void
    onDelete: () => void
}

function SectionActionsMenu({
    sectionLabel,
    canMoveUp,
    canMoveDown,
    onEdit,
    onDuplicate,
    onMoveUp,
    onMoveDown,
    onDelete,
}: SectionActionsMenuProps) {
    return (
        <div
            role="menu"
            aria-label={`Actions for ${sectionLabel}`}
            className="absolute right-0 top-full z-30 mt-2 w-56 overflow-hidden rounded-xl border border-neutral-200 bg-white py-2 shadow-xl dark:border-neutral-700 dark:bg-neutral-950"
        >
            <div className="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">
                Section Actions
            </div>

            <MenuButton onClick={onEdit}>
                <span aria-hidden="true">✎</span>
                Edit
            </MenuButton>

            <MenuButton onClick={onDuplicate}>
                <span aria-hidden="true">⧉</span>
                Duplicate
            </MenuButton>

            <MenuButton onClick={onMoveUp} disabled={!canMoveUp}>
                <span aria-hidden="true">↑</span>
                Move Up
            </MenuButton>

            <MenuButton onClick={onMoveDown} disabled={!canMoveDown}>
                <span aria-hidden="true">↓</span>
                Move Down
            </MenuButton>

            <div className="my-2 border-t border-neutral-200 dark:border-neutral-800" />

            <button
                type="button"
                role="menuitem"
                onClick={onDelete}
                className="flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
            >
                <span aria-hidden="true">×</span>
                Delete
            </button>
        </div>
    )
}

function MenuButton({
    children,
    disabled = false,
    onClick,
}: {
    children: ReactNode
    disabled?: boolean
    onClick: () => void
}) {
    return (
        <button
            type="button"
            role="menuitem"
            disabled={disabled}
            onClick={onClick}
            className="flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:text-neutral-300 dark:text-neutral-200 dark:hover:bg-neutral-900 dark:disabled:text-neutral-700"
        >
            {children}
        </button>
    )
}

function getSectionLabel(type: string, definitions: SectionDefinition[]): string {
    const definition = definitions.find((item) => item.type === type)

    return definition?.label ?? formatIdentifier(type)
}

function getTemplateLabel(
    type: string,
    template: string,
    definitions: SectionDefinition[],
): string {
    const definition = definitions.find((item) => item.type === type)

    const templateDefinition = definition?.templates.find((item) => item.key === template)

    return templateDefinition?.label ?? formatIdentifier(template)
}

function formatIdentifier(value: string): string {
    return value
        .split(/[_-]/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}
