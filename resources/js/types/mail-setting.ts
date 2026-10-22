export interface MailSettingEditorData {
    enabled: boolean
    mailer: string
    host: string | null
    port: number | null
    username: string | null
    has_password: boolean
    encryption: string | null
    from_address: string | null
    from_name: string | null
    admin_order_notification_email: string | null
}
