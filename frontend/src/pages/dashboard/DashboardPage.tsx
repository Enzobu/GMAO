import {
  Activity,
  CarFront,
  Shield,
  Wrench,
} from "lucide-react"

import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

const stats = [
  {
    title: "Véhicules",
    value: "12",
    icon: CarFront,
  },
  {
    title: "Entretiens",
    value: "4",
    icon: Wrench,
  },
  {
    title: "Assurances",
    value: "8",
    icon: Shield,
  },
  {
    title: "Activité",
    value: "92%",
    icon: Activity,
  },
]

export default function DashboardPage() {
  return (
    <div className="space-y-8">
      {/* Title */}
      <div>
        <h1 className="text-4xl font-bold tracking-tight">
          Dashboard
        </h1>

        <p className="mt-2 text-muted-foreground">
          Vue globale de votre parc automobile.
        </p>
      </div>

      {/* Stats */}
      <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        {stats.map((stat) => {
          const Icon = stat.icon

          return (
            <Card
              key={stat.title}
              className="rounded-3xl border-border bg-card shadow-sm"
            >
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                  {stat.title}
                </CardTitle>

                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <Icon className="h-5 w-5" />
                </div>
              </CardHeader>

              <CardContent>
                <p className="text-4xl font-bold tracking-tight">
                  {stat.value}
                </p>
              </CardContent>
            </Card>
          )
        })}
      </div>

      {/* Big card */}
      <Card className="rounded-3xl border-border bg-card shadow-sm">
        <CardHeader>
          <CardTitle>
            Activité récente
          </CardTitle>
        </CardHeader>

        <CardContent>
          <div className="space-y-4">
            <div className="rounded-2xl border border-border bg-muted/20 p-4">
              <p className="font-medium">
                Renault Clio 4
              </p>

              <p className="text-sm text-muted-foreground">
                Vidange effectuée il y a 2 jours
              </p>
            </div>

            <div className="rounded-2xl border border-border bg-muted/20 p-4">
              <p className="font-medium">
                Peugeot 308
              </p>

              <p className="text-sm text-muted-foreground">
                Contrôle technique expirant dans 12 jours
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}