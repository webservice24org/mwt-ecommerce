const sectionEditorTypes = new Set<string>([
    'call_to_action',
    'content',
    'features_benefits',
    'featured_products',
    'brands',
    'hero',
    'product_categories',
    'product_collection',
    'promotional_banner',
    'spacer_divider',
])

export function hasSectionEditor(type: string): boolean {
    return sectionEditorTypes.has(type)
}
