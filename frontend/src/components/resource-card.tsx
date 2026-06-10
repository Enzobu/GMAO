import type { ReactNode } from "react"
import { Link } from "react-router-dom"

import {
  CARD_LINK_CLASS,
  RESOURCE_CARD_CLASS,
  RESOURCE_META_CLASS,
} from "@/components/list-page-classes"
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

export function ResourceCard({
  title,
  to,
  ariaLabel,
  children,
  footer,
  className,
}: Readonly<{
  title: ReactNode
  to: string
  ariaLabel: string
  children: ReactNode
  footer?: ReactNode
  className?: string
}>) {
  const cardClassName = className
    ? `${RESOURCE_CARD_CLASS} ${className}`
    : RESOURCE_CARD_CLASS

  return (
    <Card className={cardClassName}>
      <Link to={to} className={CARD_LINK_CLASS} aria-label={ariaLabel} />
      <CardHeader>
        <CardTitle className="flex flex-wrap items-center gap-2">
          {title}
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {children}
      </CardContent>
      {footer && (
        <CardFooter className="relative z-20 justify-end gap-2">
          {footer}
        </CardFooter>
      )}
    </Card>
  )
}

export function ResourceMeta({ children }: Readonly<{ children: ReactNode }>) {
  return <div className={RESOURCE_META_CLASS}>{children}</div>
}
