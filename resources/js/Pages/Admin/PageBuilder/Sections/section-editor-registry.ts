const sectionEditorTypes = new Set<string>([
    'content',
    'featured_products',
    'hero',
    'product_categories',
    'product_collection',
    'promotional_banner',
])

export function hasSectionEditor(type: string): boolean {
    return sectionEditorTypes.has(type)
}
