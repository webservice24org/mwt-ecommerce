const sectionEditorTypes = new Set<string>(['featured_products'])

export function hasSectionEditor(type: string): boolean {
    return sectionEditorTypes.has(type)
}
