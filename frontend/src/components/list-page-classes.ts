export const CARD_LINK_CLASS = [
  "absolute inset-0 z-10 rounded-xl focus-visible:outline-none",
  "focus-visible:ring-3 focus-visible:ring-ring/50",
].join(" ")

export const RESOURCE_CARD_CLASS = [
  "relative border border-foreground/10 ring-0 transition-colors",
  "hover:border-primary/35 hover:bg-muted/30",
].join(" ")

export const RESOURCE_META_CLASS = [
  "flex flex-wrap gap-x-4 gap-y-1 text-sm",
  "text-muted-foreground",
].join(" ")

export const FILTER_GRID_CLASS = [
  "grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3",
  "xl:grid-cols-5",
].join(" ")

export const FILTER_GRID_WIDE_CLASS = [
  "grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3",
  "xl:grid-cols-6",
].join(" ")

export const READ_ONLY_BADGE_CLASS = [
  "border-amber-500/30 bg-amber-500/10 text-amber-700",
  "dark:text-amber-300",
].join(" ")
