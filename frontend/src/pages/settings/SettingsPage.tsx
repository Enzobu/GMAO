import { Check } from "lucide-react"

import { palettes } from "@/lib/palettes"
import { usePaletteStore } from "@/stores/palette-store"

import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

const paletteCardClassName = [
  "group rounded-3xl border bg-card p-4 text-left transition-all",
  "hover:-translate-y-0.5 hover:border-primary/70 hover:shadow-md",
  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring",
]

export default function SettingsPage() {
  const palette = usePaletteStore((state) => state.palette)
  const setPalette = usePaletteStore((state) => state.setPalette)

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-4xl font-bold tracking-tight">
          Paramètres
        </h1>

        <p className="mt-2 text-muted-foreground">
          Personnalisez l'apparence de l'application.
        </p>
      </div>

      <Card className="rounded-3xl border-border bg-card shadow-sm">
        <CardHeader>
          <CardTitle>
            Palette de couleurs
          </CardTitle>
        </CardHeader>

        <CardContent className="space-y-6">
          <p className="max-w-2xl text-sm text-muted-foreground">
            La palette pilote les éléments actifs, les boutons primaires,
            les états de survol, les icônes mises en avant et les couleurs
            de focus. Elle fonctionne avec les modes clair, sombre et
            système.
          </p>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {palettes.map((item) => {
              const isSelected = item.name === palette

              return (
                <button
                  key={item.name}
                  type="button"
                  className={[
                    ...paletteCardClassName,
                    isSelected ? "border-primary shadow-sm" : "border-border",
                  ].join(" ")}
                  onClick={() => setPalette(item.name)}
                >
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex items-center gap-3">
                      <span
                        className={[
                          "flex h-11 w-11 shrink-0 items-center",
                          "justify-center rounded-2xl text-primary-foreground",
                          "shadow-sm",
                        ].join(" ")}
                        style={{ backgroundColor: item.preview }}
                      >
                        {isSelected && <Check className="h-5 w-5" />}
                      </span>

                      <div>
                        <p className="font-semibold">
                          {item.label}
                        </p>

                        <p className="mt-1 text-sm text-muted-foreground">
                          {item.description}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="mt-5 flex items-center gap-2">
                    {item.previewSteps.map((color) => (
                      <span
                        key={color}
                        className="h-2.5 flex-1 rounded-full"
                        style={{ backgroundColor: color }}
                      />
                    ))}
                    <span className="h-2.5 flex-1 rounded-full bg-muted" />
                  </div>
                </button>
              )
            })}
          </div>

          <div className="rounded-3xl border border-border bg-muted/20 p-5">
            <div className="mb-4 flex flex-wrap items-center gap-3">
              <Button>
                Bouton primaire
              </Button>

              <Button variant="outline">
                Bouton secondaire
              </Button>
            </div>

            <div className="grid gap-3 md:grid-cols-3">
              <div
                className="rounded-2xl bg-primary p-4 text-primary-foreground"
              >
                Élément actif
              </div>

              <div className="rounded-2xl bg-accent p-4 text-accent-foreground">
                État hover
              </div>

              <div className="rounded-2xl border border-ring p-4">
                Focus / ring
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
