import { useEffect, useState, type ReactNode } from "react"
import {
  AlertTriangle,
  BarChart3,
  CalendarClock,
  CarFront,
  ClipboardCheck,
  Shield,
  Wrench,
} from "lucide-react"
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts"

import { getDashboard } from "@/api/dashboard"
import type {
  DashboardData,
  DashboardMaintenanceHistoryItem,
  DashboardItemSeverity,
  DashboardItemType,
} from "@/types/dashboard"

import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

const itemIcons: Record<DashboardItemType, typeof Wrench> = {
  maintenance: Wrench,
  insurance: Shield,
  inspection: ClipboardCheck,
}

const severityClassNames: Record<DashboardItemSeverity, string> = {
  danger: "border-red-500/40 bg-red-500/10 text-red-500",
  warning: "border-amber-500/40 bg-amber-500/10 text-amber-500",
}

export default function DashboardPage() {
  const [dashboard, setDashboard] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    let isMounted = true

    async function loadDashboard() {
      try {
        setLoading(true)
        setError("")

        const data = await getDashboard()

        if (isMounted) {
          setDashboard(data)
        }
      } catch {
        if (isMounted) {
          setError("Impossible de charger le dashboard.")
        }
      } finally {
        if (isMounted) {
          setLoading(false)
        }
      }
    }

    void loadDashboard()

    return () => {
      isMounted = false
    }
  }, [])

  const maintenanceHealth = dashboard?.stats.maintenanceHealth
  const stats = [
    {
      title: "Véhicules",
      value: dashboard?.stats.vehicles,
      icon: CarFront,
    },
    {
      title: "Entretiens",
      value: dashboard?.stats.maintenances,
      icon: Wrench,
    },
    {
      title: "Entretiens à jour",
      value: maintenanceHealth ? `${maintenanceHealth.percentage}%` : undefined,
      subtitle: maintenanceHealth
        ? [
            `${maintenanceHealth.upToDateVehicles}`,
            `/${maintenanceHealth.totalVehicles} véhicules`,
          ].join("")
        : undefined,
      icon: ClipboardCheck,
    },
    {
      title: "Alertes",
      value: dashboard?.stats.alerts,
      icon: AlertTriangle,
    },
  ]

  return (
    <div className="min-w-0 space-y-6 sm:space-y-8">
      <div className="min-w-0">
        <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
          Dashboard
        </h1>

        <p className="mt-2 text-muted-foreground">
          Vue globale du parc automobile.
        </p>
      </div>

      {error && (
        <Card
          className={[
            "rounded-3xl border-red-500/40 bg-red-500/10",
            "text-red-500 shadow-sm",
          ].join(" ")}
        >
          <CardContent className="p-5">
            {error}
          </CardContent>
        </Card>
      )}

      <div
        className={[
          "grid min-w-0 gap-4 sm:gap-5 md:grid-cols-2",
          "xl:grid-cols-4",
        ].join(" ")}
      >
        {stats.map((stat) => {
          const Icon = stat.icon

          return (
            <Card
              key={stat.title}
              className="rounded-3xl border-border bg-card shadow-sm"
            >
              <CardHeader
                className="flex flex-row items-center justify-between pb-2"
              >
                <CardTitle
                  className={[
                    "min-w-0 break-words text-sm font-medium",
                    "text-muted-foreground",
                  ].join(" ")}
                >
                  {stat.title}
                </CardTitle>

                <div
                  className={[
                    "flex h-10 w-10 items-center justify-center",
                    "rounded-xl bg-primary/10 text-primary",
                  ].join(" ")}
                >
                  <Icon className="h-5 w-5" />
                </div>
              </CardHeader>

              <CardContent>
                <p className="text-3xl font-bold tracking-tight sm:text-4xl">
                  {loading ? "..." : stat.value ?? 0}
                </p>

                {stat.subtitle && !loading && (
                  <p className="mt-1 text-sm text-muted-foreground">
                    {stat.subtitle}
                  </p>
                )}
              </CardContent>
            </Card>
          )
        })}
      </div>

      <DashboardListCard
        title="Activité à venir"
        description={[
          "Entretiens prévus, contrôles techniques et assurances",
          "à surveiller sur les 30 prochains jours.",
        ].join(" ")}
        emptyLabel="Aucune échéance à venir."
        loading={loading}
        items={dashboard?.upcoming ?? []}
        showSeverity
      />

      <MaintenanceHistoryChart
        loading={loading}
        data={dashboard?.maintenanceHistory ?? []}
      />

      <DashboardListCard
        title="Activité récente"
        description={[
          "Entretiens, contrôles techniques et assurances ajoutés",
          "sur les 30 derniers jours.",
        ].join(" ")}
        emptyLabel="Aucune activité récente."
        loading={loading}
        items={dashboard?.recentActivity ?? []}
      />
    </div>
  )
}

type MaintenanceHistoryChartProps = Readonly<{
  loading: boolean
  data: DashboardMaintenanceHistoryItem[]
}>

type MaintenanceHistoryTooltipProps = Readonly<{
  active?: boolean
  payload?: Array<{
    value?: unknown
  }>
  label?: string | number
}>

function MaintenanceHistoryTooltip({
  active,
  payload,
  label,
}: MaintenanceHistoryTooltipProps) {
  if (!active || !payload?.length) {
    return null
  }

  const count = Number(payload[0].value ?? 0)

  return (
    <div
      className={[
        "rounded-xl border border-border bg-popover px-3 py-2",
        "text-sm text-popover-foreground shadow-md",
      ].join(" ")}
    >
      <p className="font-medium">
        {label}
      </p>
      <p className="text-muted-foreground">
        {count} entretien{count > 1 ? "s" : ""}
      </p>
    </div>
  )
}

function MaintenanceHistoryChart({
  loading,
  data,
}: MaintenanceHistoryChartProps) {
  const hasMaintenance = data.some((item) => item.count > 0)

  return (
    <Card className="min-w-0 rounded-3xl border-border bg-card shadow-sm">
      <CardHeader>
        <div className="flex items-start justify-between gap-4">
          <div>
            <CardTitle>
              Entretiens réalisés
            </CardTitle>

            <p className="mt-2 text-sm text-muted-foreground">
              Volume mensuel sur les 12 derniers mois.
            </p>
          </div>

          <div
            className={[
              "flex h-10 w-10 shrink-0 items-center justify-center",
              "rounded-xl bg-primary/10 text-primary",
            ].join(" ")}
          >
            <BarChart3 className="h-5 w-5" />
          </div>
        </div>
      </CardHeader>

      <CardContent>
        {loading ? (
          <div className="h-80 animate-pulse rounded-2xl bg-muted/40" />
        ) : (
          <div className="min-w-0 space-y-4">
            <div className="h-72 min-w-0 sm:h-80">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart
                  data={data}
                  margin={{ top: 10, right: 8, left: -20, bottom: 0 }}
                >
                  <CartesianGrid
                    vertical={false}
                    stroke="hsl(var(--border))"
                    strokeDasharray="3 3"
                  />
                  <XAxis
                    dataKey="month"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={12}
                    className="text-xs text-muted-foreground"
                  />
                  <YAxis
                    allowDecimals={false}
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    className="text-xs text-muted-foreground"
                  />
                  <Tooltip
                    cursor={{ fill: "hsl(var(--muted))", opacity: 0.35 }}
                    content={<MaintenanceHistoryTooltip />}
                  />
                  <Bar
                    dataKey="count"
                    fill="var(--primary)"
                    radius={[10, 10, 4, 4]}
                  />
                </BarChart>
              </ResponsiveContainer>
            </div>

            {!hasMaintenance && (
              <p className="text-sm text-muted-foreground">
                Aucun entretien réalisé sur les 12 derniers mois.
              </p>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  )
}

type DashboardListCardProps = Readonly<{
  title: string
  description: string
  emptyLabel: string
  loading: boolean
  items: Array<{
    type: DashboardItemType
    title: string
    subtitle: string
    meta: string
    severity?: DashboardItemSeverity
  }>
  showSeverity?: boolean
}>

function DashboardListCard({
  title,
  description,
  emptyLabel,
  loading,
  items,
  showSeverity = false,
}: DashboardListCardProps) {
  let content: ReactNode

  if (loading) {
    content = (
      <div className="space-y-3">
        {["first", "second", "third"].map((item) => (
          <div
            key={item}
            className="h-20 animate-pulse rounded-2xl bg-muted/40"
          />
        ))}
      </div>
    )
  } else if (items.length > 0) {
    content = (
      <div className="grid min-w-0 gap-3 xl:grid-cols-2">
        {items.map((item, index) => {
          const Icon = itemIcons[item.type]
          const severityClassName = item.severity
            ? severityClassNames[item.severity]
            : "border-border bg-muted/20 text-primary"

          return (
            <div
              key={`${item.type}-${item.title}-${item.meta}-${index}`}
              className={[
                "min-w-0 rounded-2xl border border-border",
                "bg-muted/20 p-4",
              ].join(" ")}
            >
              <div className="flex min-w-0 items-start gap-3 sm:gap-4">
                <div
                  className={[
                    "flex h-11 w-11 shrink-0 items-center justify-center",
                    "rounded-xl border",
                    showSeverity
                      ? severityClassName
                      : "border-border bg-primary/10 text-primary",
                  ].join(" ")}
                >
                  <Icon className="h-5 w-5" />
                </div>

                <div className="min-w-0 flex-1">
                  <div
                    className={[
                      "flex min-w-0 flex-col gap-2 sm:flex-row",
                      "sm:items-center sm:justify-between",
                    ].join(" ")}
                  >
                    <p className="min-w-0 break-words font-medium">
                      {item.title}
                    </p>

                    <span
                      className={[
                        "w-fit max-w-full break-words rounded-full px-2.5",
                        "py-1 text-xs font-medium",
                        showSeverity
                          ? severityClassName
                          : "bg-primary/10 text-primary",
                      ].join(" ")}
                    >
                      {item.meta}
                    </span>
                  </div>

                  <p
                    className={[
                      "mt-1 min-w-0 break-words text-sm",
                      "text-muted-foreground",
                    ].join(" ")}
                  >
                    {item.subtitle}
                  </p>
                </div>
              </div>
            </div>
          )
        })}
      </div>
    )
  } else {
    content = (
      <div
        className={[
          "rounded-2xl border border-dashed border-border bg-muted/20",
          "p-8 text-center text-muted-foreground",
        ].join(" ")}
      >
        {emptyLabel}
      </div>
    )
  }

  return (
    <Card className="min-w-0 rounded-3xl border-border bg-card shadow-sm">
      <CardHeader>
        <div className="flex min-w-0 items-start justify-between gap-4">
          <div className="min-w-0">
            <CardTitle>
              {title}
            </CardTitle>

            <p className="mt-2 break-words text-sm text-muted-foreground">
              {description}
            </p>
          </div>

          <div
            className={[
              "flex h-10 w-10 shrink-0 items-center justify-center",
              "rounded-xl bg-primary/10 text-primary",
            ].join(" ")}
          >
            <CalendarClock className="h-5 w-5" />
          </div>
        </div>
      </CardHeader>

      <CardContent>
        {content}
      </CardContent>
    </Card>
  )
}
