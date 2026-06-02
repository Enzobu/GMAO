import type { ReactNode } from "react"

import { Badge } from "@/components/ui/badge"

const READ_ONLY_BADGE_CLASS = [
  "border-amber-500/30 bg-amber-500/10 text-amber-700",
  "dark:text-amber-300",
].join(" ")

const WARNING_MESSAGE_CLASS = [
  "rounded-lg border border-amber-500/30 bg-amber-500/10 p-4",
  "text-sm text-amber-700 dark:text-amber-300",
].join(" ")

export { PageHeader as VehicleEventHeader } from "@/components/page-primitives"
export { MileageWarningDialog } from "@/components/mileage-warning-dialog"
export { FormActions } from "@/components/form-actions"

export function ReadOnlyBadge() {
  return (
    <Badge
      variant="outline"
      className={READ_ONLY_BADGE_CLASS}
    >
      Lecture seule
    </Badge>
  )
}

export function WarningMessage({
  children,
}: Readonly<{ children: ReactNode }>) {
  return (
    <div className={WARNING_MESSAGE_CLASS}>
      {children}
    </div>
  )
}

export {
  DetailItem,
  EmptyCard,
  ErrorMessage,
  Field,
} from "@/components/page-primitives"
