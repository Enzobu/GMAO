import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Skeleton } from "@/components/ui/skeleton"

const FORM_FIELD_PLACEHOLDERS = ["field-1", "field-2", "field-3", "field-4"]

export function ListPagePlaceholder({
  filters = 4,
  items = 4,
}: Readonly<{
  filters?: number
  items?: number
}>) {
  const filterPlaceholders = placeholderIds("filter", filters)
  const itemPlaceholders = placeholderIds("item", items)

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4">
        <div className="space-y-2">
          <Skeleton className="h-8 w-44" />
          <Skeleton className="h-4 w-28" />
        </div>
        <Skeleton className="h-10 w-36" />
      </div>

      <Card>
        <CardContent className="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-4">
          {filterPlaceholders.map((placeholder) => (
            <div key={placeholder} className="space-y-2">
              <Skeleton className="h-4 w-20" />
              <Skeleton className="h-10 w-full" />
            </div>
          ))}
        </CardContent>
      </Card>

      <Skeleton className="h-10 w-full" />

      <div className="grid gap-4 xl:grid-cols-2">
        {itemPlaceholders.map((placeholder) => (
          <Card key={placeholder}>
            <CardHeader className="space-y-3">
              <Skeleton className="h-6 w-2/3" />
              <Skeleton className="h-4 w-1/3" />
            </CardHeader>
            <CardContent className="space-y-3">
              <Skeleton className="h-4 w-full" />
              <Skeleton className="h-4 w-5/6" />
              <div className="flex gap-2">
                <Skeleton className="h-6 w-20 rounded-full" />
                <Skeleton className="h-6 w-24 rounded-full" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}

export function FormPagePlaceholder({
  sections = 3,
}: Readonly<{ sections?: number }>) {
  const sectionPlaceholders = placeholderIds("section", sections)

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Skeleton className="h-8 w-56" />
        <Skeleton className="h-4 w-80 max-w-full" />
      </div>

      {sectionPlaceholders.map((section) => (
        <Card key={section}>
          <CardHeader>
            <Skeleton className="h-6 w-40" />
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            {FORM_FIELD_PLACEHOLDERS.map((field) => (
              <div key={`${section}-${field}`} className="space-y-2">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-10 w-full" />
              </div>
            ))}
          </CardContent>
        </Card>
      ))}
    </div>
  )
}

export function DetailPagePlaceholder({
  cards = 3,
}: Readonly<{ cards?: number }>) {
  const cardPlaceholders = placeholderIds("detail", cards)

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:justify-between">
        <div className="space-y-2">
          <Skeleton className="h-8 w-60" />
          <Skeleton className="h-4 w-44" />
        </div>
        <div className="flex gap-2">
          <Skeleton className="h-10 w-28" />
          <Skeleton className="h-10 w-28" />
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        {cardPlaceholders.map((placeholder) => (
          <Card key={placeholder}>
            <CardHeader>
              <Skeleton className="h-6 w-40" />
            </CardHeader>
            <CardContent className="space-y-3">
              <Skeleton className="h-4 w-full" />
              <Skeleton className="h-4 w-5/6" />
              <Skeleton className="h-4 w-2/3" />
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}

function placeholderIds(prefix: string, count: number) {
  return Array.from({ length: count }, (_, index) => `${prefix}-${index + 1}`)
}
