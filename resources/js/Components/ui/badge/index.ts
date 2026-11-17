import { cva, type VariantProps } from 'class-variance-authority';

export const badgeVariants = cva(
    'inline-flex items-center gap-1.5 rounded-pill px-3 py-1 text-xs font-semibold select-none',
    {
        variants: {
            variant: {
                default: 'bg-primary-100 text-navy-800',
                secondary: 'bg-[#E8EAF2] text-[#4A4F73]',
                outline: 'border border-border text-text-muted bg-surface',
                success: 'bg-[#C9F0E6] text-[#0F6F5B]',
                warning: 'bg-[#FFF1D6] text-[#8A5A00]',
                danger: 'bg-[#FDE3E4] text-[#B3262B]',
                destructive: 'bg-[#FDE3E4] text-[#B3262B]',
                info: 'bg-primary-100 text-[#14598F]',
                teal: 'bg-accent-100 text-accent-700',
                amber: 'bg-[#FFF1D6] text-[#8A5A00]',
                rose: 'bg-[#FDE3E4] text-[#B3262B]',
                emerald: 'bg-[#C9F0E6] text-[#0F6F5B]',
                sky: 'bg-primary-100 text-[#14598F]',
                cyan: 'bg-primary-100 text-[#14598F]',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
export { default as Badge } from './Badge.vue';
