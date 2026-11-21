<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'hero' | 'navy' | 'inverted';
        heightClass?: string;
        flip?: boolean;
    }>(),
    {
        variant: 'hero',
        heightClass: 'h-10 sm:h-16 lg:h-20',
        flip: false,
    },
);
</script>

<template>
    <div
        class="relative w-full overflow-hidden pointer-events-none select-none leading-none"
        :class="[heightClass, flip ? 'rotate-180' : '']"
        aria-hidden="true"
    >
        <svg
            class="waves-svg block w-full h-full"
            xmlns="http://www.w3.org/2000/svg"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            viewBox="0 24 150 28"
            preserveAspectRatio="none"
            shape-rendering="auto"
        >
            <defs>
                <path
                    id="gentle-wave"
                    d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z"
                />
            </defs>

            <!-- Hero Variant (Fresh laundry water over light background) -->
            <g v-if="variant === 'hero'" class="wave-parallax">
                <use
                    href="#gentle-wave"
                    x="48"
                    y="0"
                    fill="rgba(31, 127, 196, 0.12)"
                    class="wave-layer wave-layer-1"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="3"
                    fill="rgba(57, 197, 168, 0.18)"
                    class="wave-layer wave-layer-2"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="5"
                    fill="rgba(31, 60, 136, 0.10)"
                    class="wave-layer wave-layer-3"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="7"
                    fill="rgba(57, 197, 168, 0.32)"
                    class="wave-layer wave-layer-4"
                />
            </g>

            <!-- Navy Variant (Flowing into deep navy section #081050) -->
            <g v-else-if="variant === 'navy'" class="wave-parallax">
                <use
                    href="#gentle-wave"
                    x="48"
                    y="0"
                    fill="rgba(31, 127, 196, 0.25)"
                    class="wave-layer wave-layer-1"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="3"
                    fill="rgba(57, 197, 168, 0.35)"
                    class="wave-layer wave-layer-2"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="5"
                    fill="rgba(19, 39, 111, 0.65)"
                    class="wave-layer wave-layer-3"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="7"
                    fill="#081050"
                    class="wave-layer wave-layer-4"
                />
            </g>

            <!-- Inverted Variant (Flowing into light background #F4F8FB) -->
            <g v-else class="wave-parallax">
                <use
                    href="#gentle-wave"
                    x="48"
                    y="0"
                    fill="rgba(57, 197, 168, 0.20)"
                    class="wave-layer wave-layer-1"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="3"
                    fill="rgba(31, 127, 196, 0.25)"
                    class="wave-layer wave-layer-2"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="5"
                    fill="rgba(244, 248, 251, 0.60)"
                    class="wave-layer wave-layer-3"
                />
                <use
                    href="#gentle-wave"
                    x="48"
                    y="7"
                    fill="#F4F8FB"
                    class="wave-layer wave-layer-4"
                />
            </g>
        </svg>
    </div>
</template>

<style scoped>
.wave-layer {
    will-change: transform;
}

.wave-layer-1 {
    animation: wave-slide 25s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
}

.wave-layer-2 {
    animation: wave-slide 18s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
    animation-delay: -5s;
}

.wave-layer-3 {
    animation: wave-slide 12s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
    animation-delay: -3s;
}

.wave-layer-4 {
    animation: wave-slide 8s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
    animation-delay: -2s;
}

@keyframes wave-slide {
    0% {
        transform: translate3d(-90px, 0, 0);
    }
    100% {
        transform: translate3d(85px, 0, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .wave-layer {
        animation: none !important;
    }
}
</style>

