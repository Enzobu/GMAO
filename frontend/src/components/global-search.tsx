import { useEffect, useEffectEvent, useMemo, useRef, useState } from "react"
import { useNavigate } from "react-router-dom"
import {
  CarFront,
  Loader2,
  Search,
  User,
  Warehouse,
  Wrench,
} from "lucide-react"

import { getInterventions } from "@/api/interventions"
import { getParts } from "@/api/parts"
import { getUsers } from "@/api/users"
import { getVehicles } from "@/api/vehicles"
import { Input } from "@/components/ui/input"
import { useAuthStore } from "@/stores/auth-store"
import type { Intervention } from "@/types/intervention"
import type { Part } from "@/types/part"
import type { AppUser } from "@/types/user"
import type { Vehicle } from "@/types/vehicle"
import {
  formatDateTime,
  interventionStatusLabel,
  vehicleDisplayName,
} from "@/lib/intervention-utils"
import { capitalizeFirstLetter } from "@/lib/text-format"
import { cn } from "@/lib/utils"

type SearchCategory = "vehicle" | "intervention" | "part" | "user"

type SearchResult = Readonly<{
  id: string
  category: SearchCategory
  title: string
  description: string
  to: string
  searchable: string
}>

type SearchData = Readonly<{
  vehicles: Vehicle[]
  interventions: Intervention[]
  parts: Part[]
  users: AppUser[]
}>

const MIN_SEARCH_LENGTH = 2

const categoryLabels: Record<SearchCategory, string> = {
  vehicle: "Véhicules",
  intervention: "Interventions",
  part: "Stock",
  user: "Utilisateurs",
}

const categoryIcons = {
  vehicle: CarFront,
  intervention: Wrench,
  part: Warehouse,
  user: User,
}

export function GlobalSearch({ className }: Readonly<{ className?: string }>) {
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const searchInputRef = useRef<HTMLInputElement>(null)
  const containerRef = useRef<HTMLDivElement>(null)
  const [query, setQuery] = useState("")
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [hasLoaded, setHasLoaded] = useState(false)
  const [data, setData] = useState<SearchData>({
    vehicles: [],
    interventions: [],
    parts: [],
    users: [],
  })
  const loadSearchDataEvent = useEffectEvent(loadSearchData)

  useEffect(() => {
    function handleSearchShortcut(event: KeyboardEvent) {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
        event.preventDefault()
        searchInputRef.current?.focus()
        searchInputRef.current?.select()
        setIsOpen(true)
        loadSearchDataEvent()
      }
    }

    function handlePointerDown(event: PointerEvent) {
      if (!containerRef.current?.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    globalThis.addEventListener("keydown", handleSearchShortcut)
    globalThis.addEventListener("pointerdown", handlePointerDown)

    return () => {
      globalThis.removeEventListener("keydown", handleSearchShortcut)
      globalThis.removeEventListener("pointerdown", handlePointerDown)
    }
  }, [])

  const results = useMemo(() => {
    const normalizedQuery = normalize(query)

    if (normalizedQuery.length < MIN_SEARCH_LENGTH) {
      return []
    }

    return buildResults(data)
      .filter((result) =>
        normalize(result.searchable).includes(normalizedQuery),
      )
      .slice(0, 12)
  }, [data, query])

  const groupedResults = useMemo(() => groupResults(results), [results])

  async function loadSearchData() {
    if (hasLoaded || isLoading) {
      return
    }

    setIsLoading(true)

    const [vehicles, interventions, parts, users] = await Promise.all([
      getVehicles().catch(() => []),
      getInterventions().catch(() => []),
      getParts().catch(() => []),
      isAdmin ? getUsers().catch(() => []) : Promise.resolve([]),
    ])

    setData({ vehicles, interventions, parts, users })
    setHasLoaded(true)
    setIsLoading(false)
  }

  function openResult(result: SearchResult) {
    setQuery("")
    setIsOpen(false)
    navigate(result.to)
  }

  function handleKeyDown(event: React.KeyboardEvent<HTMLInputElement>) {
    if (event.key === "Escape") {
      setIsOpen(false)
      return
    }

    if (event.key === "Enter" && results[0]) {
      event.preventDefault()
      openResult(results[0])
    }
  }

  function renderDropdownContent() {
    if (isLoading) {
      return (
        <div
          className={cn(
            "flex items-center gap-2 px-4 py-3 text-sm",
            "text-muted-foreground",
          )}
        >
          <Loader2 className="size-4 animate-spin" />
          Recherche en cours...
        </div>
      )
    }

    if (query.trim().length < MIN_SEARCH_LENGTH) {
      return (
        <div className="px-4 py-3 text-sm text-muted-foreground">
          Saisissez au moins {MIN_SEARCH_LENGTH} caractères.
        </div>
      )
    }

    if (results.length === 0) {
      return (
        <div className="px-4 py-3 text-sm text-muted-foreground">
          Aucun résultat trouvé.
        </div>
      )
    }

    return (
      <div className="max-h-[28rem] overflow-y-auto py-2">
        {groupedResults.map(([category, categoryResults]) => (
          <SearchResultGroup
            key={category}
            category={category}
            results={categoryResults}
            onOpenResult={openResult}
          />
        ))}
      </div>
    )
  }

  const shouldShowDropdown = isOpen && (query.length > 0 || isLoading)

  return (
    <div
      ref={containerRef}
      className={cn("relative w-full min-w-0 max-w-lg", className)}
    >
      <Search
        className={cn(
          "pointer-events-none absolute top-1/2 left-3 size-4",
          "-translate-y-1/2 text-muted-foreground",
        )}
      />
      <Input
        ref={searchInputRef}
        value={query}
        placeholder="Rechercher..."
        className={cn(
          "h-11 rounded-xl border-border bg-card pr-10 pl-10",
          "sm:h-12 sm:pr-20",
        )}
        onFocus={() => {
          setIsOpen(true)
          void loadSearchData()
        }}
        onChange={(event) => {
          setQuery(event.target.value)
          setIsOpen(true)
          void loadSearchData()
        }}
        onKeyDown={handleKeyDown}
      />

      <div
        className={cn(
          "pointer-events-none absolute right-3 top-1/2 hidden",
          "-translate-y-1/2 items-center gap-1 text-xs",
          "text-muted-foreground sm:flex",
        )}
      >
        <kbd
          className={cn(
            "rounded-md border border-border bg-muted px-1.5 py-0.5",
            "font-sans",
          )}
        >
          Ctrl
        </kbd>
        <kbd
          className={cn(
            "rounded-md border border-border bg-muted px-1.5 py-0.5",
            "font-sans",
          )}
        >
          K
        </kbd>
      </div>

      {shouldShowDropdown && (
        <div
          className={cn(
            "absolute top-full right-0 left-0 z-50 mt-2",
            "min-w-[min(22rem,calc(100vw-2rem))] overflow-hidden",
            "rounded-xl border bg-popover text-popover-foreground",
            "shadow-xl sm:min-w-0",
          )}
        >
          {renderDropdownContent()}
        </div>
      )}
    </div>
  )
}

type SearchResultGroupProps = Readonly<{
  category: SearchCategory
  results: SearchResult[]
  onOpenResult: (result: SearchResult) => void
}>

function SearchResultGroup({
  category,
  results,
  onOpenResult,
}: SearchResultGroupProps) {
  const Icon = categoryIcons[category]

  return (
    <div className="py-1">
      <div
        className={cn(
          "flex items-center gap-2 px-3 py-1.5 text-xs font-medium",
          "text-muted-foreground",
        )}
      >
        <Icon className="size-3.5" />
        {categoryLabels[category]}
      </div>
      {results.map((result) => (
        <SearchResultButton
          key={result.id}
          result={result}
          onOpenResult={onOpenResult}
        />
      ))}
    </div>
  )
}

type SearchResultButtonProps = Readonly<{
  result: SearchResult
  onOpenResult: (result: SearchResult) => void
}>

function SearchResultButton({
  result,
  onOpenResult,
}: SearchResultButtonProps) {
  return (
    <button
      type="button"
      className={cn(
        "grid w-full gap-0.5 px-4 py-2 text-left text-sm",
        "hover:bg-muted focus:bg-muted focus:outline-none",
      )}
      onClick={() => onOpenResult(result)}
    >
      <span className="font-medium">{result.title}</span>
      <span className="truncate text-xs text-muted-foreground">
        {result.description}
      </span>
    </button>
  )
}

function buildResults(data: SearchData): SearchResult[] {
  return [
    ...data.vehicles.filter(isVisible).map(vehicleResult),
    ...data.interventions.filter(isVisible).map(interventionResult),
    ...data.parts.filter(isVisible).map(partResult),
    ...data.users.filter(isVisible).map(userResult),
  ]
}

function vehicleResult(vehicle: Vehicle): SearchResult {
  const title = vehicleDisplayName(vehicle)
  const vehicleDetails = [
    capitalizeFirstLetter(vehicle.brand),
    capitalizeFirstLetter(vehicle.model),
  ]
    .filter(Boolean)
    .join(" ")
  const registration = vehicle.registration.toUpperCase()
  const description = `${registration} - ${vehicleDetails}`

  return {
    id: `vehicle-${vehicle.id}`,
    category: "vehicle",
    title,
    description,
    to: `/vehicles/${vehicle.id}`,
    searchable: [
      title,
      vehicle.name,
      vehicle.brand,
      vehicle.model,
      vehicle.registration,
      vehicle.vin,
    ]
      .filter(Boolean)
      .join(" "),
  }
}

function interventionResult(intervention: Intervention): SearchResult {
  const vehicle = intervention.vehicle
  const type = intervention.maintenanceType?.name ?? "Intervention"
  const status = interventionStatusLabel(intervention.status)
  const date =
    intervention.finishedAt
    ?? intervention.startedAt
    ?? intervention.plannedAt
    ?? intervention.createdAt
  const vehicleLabel = vehicle
    ? `${vehicleDisplayName(vehicle)} ${vehicle.registration}`
    : "Véhicule inconnu"
  const to = vehicle
    ? `/vehicles/${vehicle.id}/interventions/${intervention.id}`
    : `/interventions/${intervention.id}`

  return {
    id: `intervention-${intervention.id}`,
    category: "intervention",
    title: type,
    description: `${status} - ${formatDateTime(date)} - ${vehicleLabel}`,
    to,
    searchable: [type, status, date, formatDateTime(date), vehicleLabel]
      .filter(Boolean)
      .join(" "),
  }
}

function partResult(part: Part): SearchResult {
  const type = part.partType?.name ?? "Pièce"
  const vehicles = part.vehicles
    ?.map((vehicle) => vehicle.registration)
    .join(", ")

  return {
    id: `part-${part.id}`,
    category: "part",
    title: type,
    description: `Stock: ${part.quantity} - ${vehicles || "Aucun véhicule"}`,
    to: `/parts/${part.id}`,
    searchable: [type, part.partType?.description, part.note, vehicles]
      .filter(Boolean)
      .join(" "),
  }
}

function userResult(user: AppUser): SearchResult {
  const title = userLabel(user)

  return {
    id: `user-${user.id}`,
    category: "user",
    title,
    description: user.email,
    to: `/users/${user.id}`,
    searchable: [user.firstname, user.lastname, user.email]
      .filter(Boolean)
      .join(" "),
  }
}

function groupResults(
  results: SearchResult[],
): [SearchCategory, SearchResult[]][] {
  const categories: SearchCategory[] = [
    "vehicle",
    "intervention",
    "part",
    "user",
  ]

  return categories
    .map((category): [SearchCategory, SearchResult[]] => [
      category,
      results.filter((result) => result.category === category),
    ])
    .filter(([, categoryResults]) => categoryResults.length > 0)
}

function userLabel(user: AppUser) {
  const firstName = capitalizeFirstLetter(user.firstname)
  const lastName = capitalizeFirstLetter(user.lastname)
  const fullName = `${firstName} ${lastName}`.trim()

  return fullName || user.email
}

function isVisible(item: { isDeleted?: boolean; deleted?: boolean }) {
  return item.isDeleted !== true && item.deleted !== true
}

function normalize(value: string) {
  return value
    .toLowerCase()
    .normalize("NFD")
    .replaceAll(/[\u0300-\u036f]/g, "")
}
