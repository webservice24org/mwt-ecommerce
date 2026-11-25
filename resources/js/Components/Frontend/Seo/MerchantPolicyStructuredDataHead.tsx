import type {
    PageProps,
} from '@/types'

import {
    Head,
    usePage,
} from '@inertiajs/react'

type StructuredDataDocument = {
    '@context': string
    '@graph': Array<Record<string, unknown>>
}

type MerchantPolicyPageProps =
    PageProps<{
        merchantPolicyStructuredData?:
            StructuredDataDocument | null
    }>

export default function MerchantPolicyStructuredDataHead() {
    const {
        merchantPolicyStructuredData,
    } =
        usePage<MerchantPolicyPageProps>()
            .props

    if (
        !merchantPolicyStructuredData
    ) {
        return null
    }

    return (
        <Head>
            <script
                head-key="merchant-policy-structured-data"
                type="application/ld+json"
            >
                {JSON.stringify(
                    merchantPolicyStructuredData,
                )}
            </script>
        </Head>
    )
}
