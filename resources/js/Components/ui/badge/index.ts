import { cva, type VariantProps } from 'class-variance-authority';

export const badgeVariants = cva(
    'inline-flex items-center rounded-[6px] border-2 border-black px-2.5 py-0.5 text-xs font-black shadow-[2px_2px_0px_#000] select-none transition-transform',
    {
        variants: {
            variant: {
                default: 'bg-[#FFEB3B] text-black',
                secondary: 'bg-white text-black',
                destructive: 'bg-[#FF5252] text-white',
                outline: 'bg-white text-black',
                success: 'bg-[#22C55E] text-black',
                warning: 'bg-[#F59E0B] text-black',
                teal: 'bg-[#2196F3] text-white',
                cyan: 'bg-[#06b6d4] text-black',
                amber: 'bg-[#F59E0B] text-black',
                rose: 'bg-[#FF5252] text-white',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
export { default as Badge } from './Badge.vue';
