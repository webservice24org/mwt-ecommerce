const sectionEditorTypes = new Set<string>(['featured_products', 'hero', 'product_categories'])

export function hasSectionEditor(type: string): boolean {
    return sectionEditorTypes.has(type)
}
