import { cva, type VariantProps } from 'class-variance-authority';

export const badgeVariants = cva(
    'inline-flex items-center rounded-[4px] border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-3 focus:ring-[#6366F1]/12 select-none',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-[#6366F1] text-white hover:bg-[#4F46E5]',
                secondary: 'border border-[#E8E8EC] bg-[#FAFAFA] text-[#6B6B6B] hover:bg-slate-100',
                destructive: 'border border-[#EF4444]/20 bg-[#EF4444]/10 text-[#EF4444]',
                outline: 'border border-[#E8E8EC] text-[#0A0A0A]',
                success: 'border border-[#10B981]/20 bg-[#10B981]/10 text-[#10B981]',
                warning: 'border border-[#F59E0B]/20 bg-[#F59E0B]/10 text-[#F59E0B]',
                teal: 'border border-[#6366F1]/20 bg-[#6366F1]/10 text-[#6366F1]',
                cyan: 'border border-[#06b6d4]/20 bg-[#06b6d4]/10 text-[#0e7490]',
                amber: 'border border-[#F59E0B]/20 bg-[#F59E0B]/10 text-[#F59E0B]',
                rose: 'border border-[#EF4444]/20 bg-[#EF4444]/10 text-[#EF4444]',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
export { default as Badge } from './Badge.vue';
