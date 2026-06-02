import type { ReactNode } from "react"
import { Link } from "react-router-dom"
import { Plus, Search, X } from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { READ_ONLY_BADGE_CLASS } from "@/components/list-page-classes"

type ListPageHeaderProps = Readonly<{
  title: string
  countLabel: string
  addTo?: string
  addLabel?: string
}>

export function ListPageHeader({
  title,
  countLabel,
  addTo,
  addLabel,
}: ListPageHeaderProps) {
  return (
    <div className={PAGE_HEADER_CLASS}>
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
        <p className="text-sm text-muted-foreground">{countLabel}</p>
      </div>
      {addTo && addLabel && (
        <Button asChild>
          <Link to={addTo}>
            <Plus />
            {addLabel}
          </Link>
        </Button>
      )}
    </div>
  )
}

type SearchFieldProps = Readonly<{
  id: string
  value: string
  placeholder: string
  onChange: (value: string) => void
}>

export function SearchField({
  id,
  value,
  placeholder,
  onChange,
}: SearchFieldProps) {
  return (
    <label
      className={SEARCH_LABEL_CLASS}
      htmlFor={id}
    >
      <span>Recherche</span>
      <div className="relative min-w-0">
        <Search
          className={SEARCH_ICON_CLASS}
        />
        <Input
          id={id}
          value={value}
          onChange={(event) => onChange(event.target.value)}
          placeholder={placeholder}
          className="pl-8"
        />
      </div>
    </label>
  )
}

export function EmptyListCard({ children }: Readonly<{ children: ReactNode }>) {
  return (
    <Card>
      <CardContent className={EMPTY_CARD_CONTENT_CLASS}>
        {children}
      </CardContent>
    </Card>
  )
}

export function ResetFiltersButton({
  disabled,
  onReset,
}: Readonly<{ disabled: boolean; onReset: () => void }>) {
  return (
    <div className="flex items-end">
      <Button
        variant="outline"
        className="w-full"
        onClick={onReset}
        disabled={disabled}
      >
        <X />
        Réinitialiser
      </Button>
    </div>
  )
}

export function ReadOnlyBadge() {
  return (
    <Badge variant="outline" className={READ_ONLY_BADGE_CLASS}>
      Lecture seule
    </Badge>
  )
}

const PAGE_HEADER_CLASS = [
  "flex flex-col gap-3 sm:flex-row sm:items-start",
  "sm:justify-between",
].join(" ")

const SEARCH_LABEL_CLASS = [
  "grid min-w-0 gap-1.5 text-sm font-medium sm:col-span-2",
  "lg:col-span-3 xl:col-span-1",
].join(" ")

const SEARCH_ICON_CLASS = [
  "pointer-events-none absolute top-1/2 left-2.5 size-4",
  "-translate-y-1/2 text-muted-foreground",
].join(" ")

const EMPTY_CARD_CONTENT_CLASS = [
  "py-8 text-center text-sm",
  "text-muted-foreground",
].join(" ")
