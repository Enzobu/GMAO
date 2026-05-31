import { Link, useNavigate } from "react-router-dom"
import { ArrowLeft, Gauge, SearchX } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"

export default function NotFoundPage() {
  const navigate = useNavigate()

  return (
    <div className="flex min-h-[calc(100vh-10rem)] items-center justify-center">
      <Card className="w-full max-w-2xl border border-foreground/10 bg-card/80 text-center ring-0">
        <CardContent className="space-y-6 py-12">
          <div className="mx-auto flex size-20 items-center justify-center rounded-3xl bg-primary/10 text-primary">
            <SearchX className="size-9" />
          </div>

          <div className="space-y-2">
            <p className="text-sm font-medium uppercase tracking-[0.3em] text-muted-foreground">Erreur 404</p>
            <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">Page introuvable</h1>
            <p className="mx-auto max-w-md text-sm text-muted-foreground">
              La page demandée n’existe pas, a été déplacée, ou vous avez suivi un lien expiré.
            </p>
          </div>

          <div className="flex flex-col justify-center gap-2 sm:flex-row">
            <Button variant="outline" onClick={() => navigate(-1)}>
              <ArrowLeft />
              Retour
            </Button>
            <Button asChild>
              <Link to="/dashboard">
                <Gauge />
                Tableau de bord
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
