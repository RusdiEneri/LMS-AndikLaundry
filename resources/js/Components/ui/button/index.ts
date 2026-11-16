import { cva, type VariantProps } from 'class-variance-authority';

export const buttonVariants = cva(
    'inline-flex items-center justify-center whitespace-nowrap rounded-[8px] text-sm font-extrabold transition-all duration-150 focus-visible:outline-none focus-visible:ring-0 focus-visible:border-black disabled:pointer-events-none disabled:opacity-50 select-none cursor-pointer',
    {
        variants: {
            variant: {
                default: 'border-[2.5px] border-black bg-[#FFEB3B] text-black shadow-[3px_3px_0px_#000] hover:bg-[#FDE047] hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_#000]',
                destructive: 'border-[2.5px] border-black bg-[#FF5252] text-white shadow-[3px_3px_0px_#000] hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_#000]',
                outline: 'border-[2.5px] border-black bg-white text-black shadow-[3px_3px_0px_#000] hover:bg-[#FEF08A] hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_#000]',
                secondary: 'border-[2.5px] border-black bg-[#2196F3] text-white shadow-[3px_3px_0px_#000] hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_#000]',
                ghost: 'text-black border-2 border-transparent hover:border-black hover:bg-[#FEF08A] hover:shadow-[2px_2px_0px_#000] active:translate-y-0',
                link: 'text-black underline underline-offset-4 decoration-2 hover:text-[#FF5252]',
            },
            size: {
                default: 'h-[40px] px-4 py-2',
                sm: 'h-8 px-3 text-xs',
                lg: 'h-12 px-6 text-base',
                icon: 'h-[40px] w-[40px] p-0',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
export { default as Button } from './Button.vue';
