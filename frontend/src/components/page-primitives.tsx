import type { ComponentProps, ReactNode } from "react"
import { Link } from "react-router-dom"
import { ArrowLeft } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"

export function PageHeader({
  title,
  description,
  backTo,
  backLabel = "Retour",
  actions,
}: Readonly<{
  title: string
  description?: string
  backTo?: string
  backLabel?: string
  actions?: ReactNode
}>) {
  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
        {description && <p className="text-sm text-muted-foreground">{description}</p>}
      </div>
      <div className="flex flex-wrap gap-2">
        {backTo && (
          <Button variant="outline" asChild>
            <Link to={backTo}>
              <ArrowLeft />
              {backLabel}
            </Link>
          </Button>
        )}
        {actions}
      </div>
    </div>
  )
}

export function DetailItem({ label, value, boxed = false }: Readonly<{ label: string; value: string; boxed?: boolean }>) {
  return (
    <div className={boxed ? "rounded-lg border p-3" : undefined}>
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 font-medium">{value}</div>
    </div>
  )
}

export function EmptyCard({ children }: Readonly<{ children: ReactNode }>) {
  return (
    <Card>
      <CardContent className="py-8 text-sm text-muted-foreground">{children}</CardContent>
    </Card>
  )
}

export function ErrorMessage({ children }: Readonly<{ children: ReactNode }>) {
  return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{children}</div>
}

export function Field({
  label,
  value,
  onChange,
  required,
  ...props
}: Readonly<{
  label: string
  value: string
  onChange: (value: string) => void
} & Omit<ComponentProps<typeof Input>, "value" | "onChange">>) {
  return (
    <label className="grid gap-1.5 text-sm font-medium">
      <LabelText label={label} required={required} />
      <Input value={value} required={required} onChange={(event) => onChange(event.target.value)} {...props} />
    </label>
  )
}

export function LabelText({ label, required }: Readonly<{ label: string; required?: boolean }>) {
  return (
    <span>
      {label}
      {required && <span className="text-destructive"> *</span>}
    </span>
  )
}
