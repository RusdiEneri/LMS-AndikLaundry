import { cva, type VariantProps } from 'class-variance-authority';

export const sheetVariants = cva(
    'fixed z-50 gap-4 bg-[#FFFDF5] text-black shadow-lg transition ease-in-out data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:duration-300 data-[state=open]:duration-500',
    {
        variants: {
            side: {
                top: 'inset-x-0 top-0 border-b-[3.5px] border-black shadow-[0px_6px_0px_#000] data-[state=closed]:slide-out-to-top data-[state=open]:slide-in-from-top',
                bottom: 'inset-x-0 bottom-0 border-t-[3.5px] border-black shadow-[0px_-6px_0px_#000] rounded-t-[14px] max-h-[92vh] overflow-y-auto data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom',
                left: 'inset-y-0 left-0 h-full w-3/4 border-r-[3.5px] border-black shadow-[6px_0px_0px_#000] sm:max-w-sm data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left',
                right: 'inset-y-0 right-0 h-full w-3/4 border-l-[3.5px] border-black shadow-[-6px_0px_0px_#000] sm:max-w-sm data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right',
            },
        },
        defaultVariants: {
            side: 'right',
        },
    }
);

export type SheetVariants = VariantProps<typeof sheetVariants>;

export { default as Sheet } from './Sheet.vue';
export { default as SheetTrigger } from './SheetTrigger.vue';
export { default as SheetClose } from './SheetClose.vue';
export { default as SheetContent } from './SheetContent.vue';
export { default as SheetHeader } from './SheetHeader.vue';
export { default as SheetTitle } from './SheetTitle.vue';
export { default as SheetDescription } from './SheetDescription.vue';
export { default as SheetFooter } from './SheetFooter.vue';
