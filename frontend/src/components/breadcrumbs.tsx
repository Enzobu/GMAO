import { useEffect, useMemo, useState } from "react"
import { ChevronRight, Home } from "lucide-react"
import { Link, matchPath, useLocation } from "react-router-dom"

import { getIntervention } from "@/api/interventions"
import { getPart } from "@/api/parts"
import { getUser } from "@/api/users"
import {
  getVehicleInspection,
  getVehicleInsurance,
} from "@/api/vehicle-events"
import { getVehicle } from "@/api/vehicles"
import type { Intervention } from "@/types/intervention"
import type { Part } from "@/types/part"
import type { AppUser } from "@/types/user"
import type {
  VehicleInspectionEvent,
  VehicleInsuranceEvent,
} from "@/types/vehicle-events"
import type { Vehicle } from "@/types/vehicle"

type BreadcrumbItem = Readonly<{
  label: string
  href?: string
}>

type ResourceIds = Readonly<{
  vehicleId?: string
  userId?: string
  partId?: string
  interventionId?: string
  insuranceId?: string
  inspectionId?: string
}>

type ResourceLabels = Readonly<{
  vehicle?: string
  user?: string
  part?: string
  intervention?: string
  insurance?: string
  inspection?: string
}>

type LabelState = Readonly<{
  key: string
  labels: ResourceLabels
}>

type BreadcrumbContext = Readonly<{
  ids: ResourceIds
  labels: ResourceLabels
}>

type BreadcrumbRoute = Readonly<{
  pattern: string
  ids: (params: Record<string, string | undefined>) => ResourceIds
  items: (context: BreadcrumbContext) => BreadcrumbItem[]
}>

const ROOT_ITEM: BreadcrumbItem = { label: "Accueil", href: "/dashboard" }
const VEHICLES_ITEM: BreadcrumbItem = { label: "Véhicules", href: "/vehicles" }
const PARTS_ITEM: BreadcrumbItem = { label: "Stock", href: "/parts" }
const USERS_ITEM: BreadcrumbItem = { label: "Utilisateurs", href: "/users" }
const INTERVENTIONS_ITEM: BreadcrumbItem = {
  label: "Interventions",
  href: "/interventions",
}

const STATIC_ROUTES: BreadcrumbRoute[] = [
  route("/", {}, () => [{ label: "Accueil" }]),
  route("/dashboard", {}, () => [{ label: "Accueil" }]),
  route("/settings", {}, () => [ROOT_ITEM, { label: "Paramètres" }]),
  route("/profile", {}, () => [ROOT_ITEM, { label: "Profil" }]),
  route("/configuration", {}, () => [ROOT_ITEM, { label: "Configuration" }]),
  route("/vehicles", {}, () => [ROOT_ITEM, { label: "Véhicules" }]),
  route("/vehicles/new", {}, () => [
    ROOT_ITEM,
    VEHICLES_ITEM,
    { label: "Nouveau véhicule" },
  ]),
  route("/parts", {}, () => [ROOT_ITEM, { label: "Stock" }]),
  route("/parts/new", {}, () => [
    ROOT_ITEM,
    PARTS_ITEM,
    { label: "Nouvelle pièce" },
  ]),
  route("/users", {}, () => [ROOT_ITEM, { label: "Utilisateurs" }]),
  route("/users/new", {}, () => [
    ROOT_ITEM,
    USERS_ITEM,
    { label: "Nouvel utilisateur" },
  ]),
  route("/interventions", {}, () => [ROOT_ITEM, { label: "Interventions" }]),
]

const DYNAMIC_ROUTES: BreadcrumbRoute[] = [
  route("/vehicles/:id", ({ id }) => ({ vehicleId: id }), ({ labels }) => [
    ROOT_ITEM,
    VEHICLES_ITEM,
    { label: labels.vehicle ?? "Véhicule" },
  ]),
  route("/vehicles/:id/edit", ({ id }) => ({ vehicleId: id }), vehicleItems),
  route(
    "/vehicles/:vehicleId/insurances",
    ({ vehicleId }) => ({ vehicleId }),
    vehicleInsurancesItems,
  ),
  route(
    "/vehicles/:vehicleId/insurances/new",
    ({ vehicleId }) => ({ vehicleId }),
    (context) => [
      ...vehicleInsurancesItems(context),
      { label: "Nouvelle assurance" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/insurances/:insuranceId",
    ({ vehicleId, insuranceId }) => ({ vehicleId, insuranceId }),
    (context) => [
      ...vehicleInsurancesItems(context),
      { label: context.labels.insurance ?? "Assurance" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/insurances/:insuranceId/edit",
    ({ vehicleId, insuranceId }) => ({ vehicleId, insuranceId }),
    (context) => [
      ...vehicleInsurancesItems(context),
      insuranceItem(context),
      { label: "Modifier" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/inspections",
    ({ vehicleId }) => ({ vehicleId }),
    vehicleInspectionsItems,
  ),
  route(
    "/vehicles/:vehicleId/inspections/new",
    ({ vehicleId }) => ({ vehicleId }),
    (context) => [
      ...vehicleInspectionsItems(context),
      { label: "Nouveau contrôle" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/inspections/:inspectionId",
    ({ vehicleId, inspectionId }) => ({ vehicleId, inspectionId }),
    (context) => [
      ...vehicleInspectionsItems(context),
      { label: context.labels.inspection ?? "Contrôle technique" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/inspections/:inspectionId/edit",
    ({ vehicleId, inspectionId }) => ({ vehicleId, inspectionId }),
    (context) => [
      ...vehicleInspectionsItems(context),
      inspectionItem(context),
      { label: "Modifier" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/interventions",
    ({ vehicleId }) => ({ vehicleId }),
    vehicleInterventionsItems,
  ),
  route(
    "/vehicles/:vehicleId/interventions/new",
    ({ vehicleId }) => ({ vehicleId }),
    (context) => [
      ...vehicleInterventionsItems(context),
      { label: "Nouvelle intervention" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/interventions/:interventionId",
    ({ vehicleId, interventionId }) => ({ vehicleId, interventionId }),
    (context) => [
      ...vehicleInterventionsItems(context),
      { label: context.labels.intervention ?? "Intervention" },
    ],
  ),
  route(
    "/vehicles/:vehicleId/interventions/:interventionId/edit",
    ({ vehicleId, interventionId }) => ({ vehicleId, interventionId }),
    (context) => [
      ...vehicleInterventionsItems(context),
      interventionItem(context),
      { label: "Modifier" },
    ],
  ),
  route(
    "/interventions/:id",
    ({ id }) => ({ interventionId: id }),
    (context) => [ROOT_ITEM, INTERVENTIONS_ITEM, interventionItem(context)],
  ),
  route("/parts/:id", ({ id }) => ({ partId: id }), ({ labels }) => [
    ROOT_ITEM,
    PARTS_ITEM,
    { label: labels.part ?? "Pièce" },
  ]),
  route("/parts/:id/edit", ({ id }) => ({ partId: id }), (context) => [
    ROOT_ITEM,
    PARTS_ITEM,
    partItem(context),
    { label: "Modifier" },
  ]),
  route("/users/:id", ({ id }) => ({ userId: id }), ({ labels }) => [
    ROOT_ITEM,
    USERS_ITEM,
    { label: labels.user ?? "Utilisateur" },
  ]),
  route("/users/:id/edit", ({ id }) => ({ userId: id }), (context) => [
    ROOT_ITEM,
    USERS_ITEM,
    userItem(context),
    { label: "Modifier" },
  ]),
]

const ROUTES = [...STATIC_ROUTES, ...DYNAMIC_ROUTES]

export function Breadcrumbs() {
  const location = useLocation()
  const matchedRoute = useMemo(
    () => matchedBreadcrumbRoute(location.pathname),
    [location.pathname],
  )
  const ids = useMemo(
    () => matchedRoute?.route.ids(matchedRoute.params) ?? {},
    [matchedRoute],
  )
  const idsKey = resourceIdsKey(ids)
  const [labelState, setLabelState] = useState<LabelState>({
    key: "",
    labels: {},
  })
  const labels = labelState.key === idsKey ? labelState.labels : {}

  useEffect(() => {
    let shouldIgnore = false
    const currentIds = ids
    const currentKey = idsKey

    async function loadLabels() {
      const nextLabels = await resourceLabels(currentIds)

      if (!shouldIgnore) {
        setLabelState({ key: currentKey, labels: nextLabels })
      }
    }

    void loadLabels()

    return () => {
      shouldIgnore = true
    }
  }, [ids, idsKey])

  if (!matchedRoute) {
    return null
  }

  const items = matchedRoute.route.items({ ids, labels })

  return (
    <nav aria-label="Fil d'Ariane" className="mb-4 min-w-0">
      <ol className="flex min-w-0 items-center gap-1 text-sm">
        {items.map((item, index) => (
          <BreadcrumbEntry
            key={`${item.href ?? item.label}-${index}`}
            item={item}
            isCurrent={index === items.length - 1}
          />
        ))}
      </ol>
    </nav>
  )
}

function BreadcrumbEntry({
  item,
  isCurrent,
}: Readonly<{ item: BreadcrumbItem; isCurrent: boolean }>) {
  return (
    <li className="flex min-w-0 items-center gap-1">
      {item.href && !isCurrent ? (
        <Link
          to={item.href}
          className={[
            "flex min-w-0 items-center gap-1 text-muted-foreground",
            "hover:text-foreground",
          ].join(" ")}
        >
          {item.href === "/dashboard" && <Home className="h-3.5 w-3.5" />}
          <span className="truncate">{item.label}</span>
        </Link>
      ) : (
        <span className="truncate font-medium text-foreground">
          {item.label}
        </span>
      )}

      {!isCurrent && (
        <ChevronRight
          className="h-3.5 w-3.5 shrink-0 text-muted-foreground"
        />
      )}
    </li>
  )
}

function route(
  pattern: string,
  ids: ResourceIds | BreadcrumbRoute["ids"],
  items: BreadcrumbRoute["items"],
): BreadcrumbRoute {
  return {
    pattern,
    ids: typeof ids === "function" ? ids : () => ids,
    items,
  }
}

function matchedBreadcrumbRoute(pathname: string) {
  for (const routeItem of ROUTES) {
    const match = matchPath({ path: routeItem.pattern, end: true }, pathname)

    if (match) {
      return { route: routeItem, params: match.params }
    }
  }

  return null
}

function resourceIdsKey(ids: ResourceIds) {
  return [
    ids.inspectionId ?? "",
    ids.insuranceId ?? "",
    ids.interventionId ?? "",
    ids.partId ?? "",
    ids.userId ?? "",
    ids.vehicleId ?? "",
  ].join("|")
}

async function resourceLabels(ids: ResourceIds): Promise<ResourceLabels> {
  const [vehicle, user, part, intervention, insurance, inspection] =
    await Promise.all([
      safeLoad(ids.vehicleId, getVehicle, vehicleLabel),
      safeLoad(ids.userId, getUser, userLabel),
      safeLoad(ids.partId, getPart, partLabel),
      safeLoad(ids.interventionId, getIntervention, interventionLabel),
      safeLoad(ids.insuranceId, getVehicleInsurance, insuranceLabel),
      safeLoad(ids.inspectionId, getVehicleInspection, inspectionLabel),
    ])

  return { vehicle, user, part, intervention, insurance, inspection }
}

async function safeLoad<T>(
  id: string | undefined,
  loader: (id: string) => Promise<T>,
  label: (resource: T) => string,
) {
  if (!id) {
    return undefined
  }

  try {
    return label(await loader(id))
  } catch {
    return undefined
  }
}

function vehicleItems(context: BreadcrumbContext): BreadcrumbItem[] {
  return [ROOT_ITEM, VEHICLES_ITEM, vehicleItem(context), { label: "Modifier" }]
}

function vehicleInsurancesItems(context: BreadcrumbContext): BreadcrumbItem[] {
  return [
    ROOT_ITEM,
    VEHICLES_ITEM,
    vehicleItem(context),
    {
      label: "Assurances",
      href: `/vehicles/${context.ids.vehicleId}/insurances`,
    },
  ]
}

function vehicleInspectionsItems(context: BreadcrumbContext): BreadcrumbItem[] {
  return [
    ROOT_ITEM,
    VEHICLES_ITEM,
    vehicleItem(context),
    {
      label: "Contrôles techniques",
      href: `/vehicles/${context.ids.vehicleId}/inspections`,
    },
  ]
}

function vehicleInterventionsItems(
  context: BreadcrumbContext,
): BreadcrumbItem[] {
  return [
    ROOT_ITEM,
    VEHICLES_ITEM,
    vehicleItem(context),
    {
      label: "Interventions",
      href: `/vehicles/${context.ids.vehicleId}/interventions`,
    },
  ]
}

function vehicleItem(context: BreadcrumbContext): BreadcrumbItem {
  return {
    label: context.labels.vehicle ?? "Véhicule",
    href: `/vehicles/${context.ids.vehicleId}`,
  }
}

function insuranceItem(context: BreadcrumbContext): BreadcrumbItem {
  return {
    label: context.labels.insurance ?? "Assurance",
    href: [
      `/vehicles/${context.ids.vehicleId}/insurances`,
      context.ids.insuranceId,
    ].join("/"),
  }
}

function inspectionItem(context: BreadcrumbContext): BreadcrumbItem {
  return {
    label: context.labels.inspection ?? "Contrôle technique",
    href: [
      `/vehicles/${context.ids.vehicleId}/inspections`,
      context.ids.inspectionId,
    ].join("/"),
  }
}

function interventionItem(context: BreadcrumbContext): BreadcrumbItem {
  return {
    label: context.labels.intervention ?? "Intervention",
    href: context.ids.vehicleId
      ? [
          `/vehicles/${context.ids.vehicleId}/interventions`,
          context.ids.interventionId,
        ].join("/")
      : `/interventions/${context.ids.interventionId}`,
  }
}

function partItem(context: BreadcrumbContext): BreadcrumbItem {
  return {
    label: context.labels.part ?? "Pièce",
    href: `/parts/${context.ids.partId}`,
  }
}

function userItem(context: BreadcrumbContext): BreadcrumbItem {
  return {
    label: context.labels.user ?? "Utilisateur",
    href: `/users/${context.ids.userId}`,
  }
}

function vehicleLabel(vehicle: Vehicle) {
  return `${vehicle.name} (${vehicle.registration})`
}

function userLabel(user: AppUser) {
  return `${user.firstname} ${user.lastname}`.trim() || user.email
}

function partLabel(part: Part) {
  return part.partType.name
}

function interventionLabel(intervention: Intervention) {
  return intervention.maintenanceType.name
}

function insuranceLabel(insurance: VehicleInsuranceEvent) {
  return insurance.providerName
}

function inspectionLabel(inspection: VehicleInspectionEvent) {
  return `CT du ${formatDate(inspection.inspectionDate)}`
}

function formatDate(value: string) {
  return new Intl.DateTimeFormat("fr-FR").format(new Date(value))
}
