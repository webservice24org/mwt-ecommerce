const sectionEditorTypes = new Set<string>([
    'call_to_action',
    'content',
    'features_benefits',
    'featured_products',
    'hero',
    'product_categories',
    'product_collection',
    'promotional_banner',
])

export function hasSectionEditor(type: string): boolean {
    return sectionEditorTypes.has(type)
}
