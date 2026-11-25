import type {
    StructuredDataDocument,
} from '@/types/structured-data'
import { Head } from '@inertiajs/react'

interface Props {
    document:
        | StructuredDataDocument
        | null
}

export default function StorefrontGlobalStructuredData({
    document,
}: Props) {
    if (!document) {
        return null
    }

    return (
        <Head>
            <script
                head-key="global-structured-data"
                type="application/ld+json"
            >
                {JSON.stringify(
                    document,
                )}
            </script>
        </Head>
    )
}