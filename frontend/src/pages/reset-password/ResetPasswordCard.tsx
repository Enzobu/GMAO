import type { FormEvent, ReactNode } from "react"
import { Link } from "react-router-dom"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

export function ResetPasswordCard({
  title,
  message,
  error,
  isLoading,
  submitLabel,
  loadingLabel,
  children,
  onSubmit,
}: Readonly<{
  title: string
  message: string
  error: string
  isLoading: boolean
  submitLabel: string
  loadingLabel: string
  children: ReactNode
  onSubmit: (event: FormEvent) => void
}>) {
  return (
    <Card
      className="w-full max-w-md rounded-3xl border-border bg-card shadow-xl"
    >
      <CardHeader>
        <CardTitle className="text-2xl">
          {title}
        </CardTitle>
      </CardHeader>

      <CardContent>
        <form onSubmit={onSubmit} className="space-y-4">
          {children}

          {message && (
            <p className="text-sm text-primary">
              {message}
            </p>
          )}

          {error && (
            <p className="text-sm text-red-500">
              {error}
            </p>
          )}

          <Button type="submit" className="w-full" disabled={isLoading}>
            {isLoading ? loadingLabel : submitLabel}
          </Button>

          <Button asChild variant="ghost" className="w-full">
            <Link to="/login">
              Retour à la connexion
            </Link>
          </Button>
        </form>
      </CardContent>
    </Card>
  )
}
