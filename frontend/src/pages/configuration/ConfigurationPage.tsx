import { useEffect, useMemo, useState } from "react"
import type { FormEvent, ReactNode } from "react"
import { AxiosError } from "axios"
import { Pencil, Plus, Search, Trash2 } from "lucide-react"

import {
  createInspectionCenter,
  createMaintenanceType,
  createPartType,
  deleteInspectionCenter,
  deleteMaintenanceType,
  deletePartType,
  getInspectionCentersConfiguration,
  getMaintenanceTypes,
  getPartTypes,
  updateInspectionCenter,
  updateMaintenanceType,
  updatePartType,
} from "@/api/configuration"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { LabelText } from "@/components/page-primitives"
import { Card, CardContent } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import type {
  ConfigurationItem,
  ConfigurationPayload,
  InspectionCenterConfigurationItem,
  InspectionCenterConfigurationPayload,
} from "@/types/configuration"

type ResourceKind = "maintenance" | "part"

interface ResourceConfig {
  kind: ResourceKind
  title: string
  description: string
  emptyTitle: string
  emptyDescription: string
  deleteTitle: string
  deleteDescription: (item: ConfigurationItem) => string
  getItems: () => Promise<ConfigurationItem[]>
  createItem: (payload: ConfigurationPayload) => Promise<ConfigurationItem>
  updateItem: (
    id: number,
    payload: ConfigurationPayload,
  ) => Promise<ConfigurationItem>
  deleteItem: (id: number) => Promise<void>
}

type ConfigurationFormDialogProps = Readonly<{
  item: ConfigurationItem | "new" | null
  title: string
  error: string | null
  isSaving: boolean
  onOpenChange: (open: boolean) => void
  onSubmit: (payload: ConfigurationPayload) => Promise<void>
}>

type InspectionCenterFormDialogProps = Readonly<{
  item: InspectionCenterConfigurationItem | "new" | null
  error: string | null
  isSaving: boolean
  onOpenChange: (open: boolean) => void
  onSubmit: (payload: InspectionCenterConfigurationPayload) => Promise<void>
}>

type ConfigurationSearchToolbarProps = Readonly<{
  id: string
  value: string
  placeholder: string
  onSearchChange: (value: string) => void
  onAdd: () => void
}>

type EmptyConfigurationStateProps = Readonly<{
  title: string
  description: string
}>

type ItemCountBadgeProps = Readonly<{
  search: string
  filteredCount: number
  totalCount: number
}>

type ConfigurationItemActionsProps = Readonly<{
  onEdit: () => void
  onDelete: () => void
}>

type PanelErrorProps = Readonly<{
  message: string | null
  hidden: boolean
}>

const configurationCardClassName =
  "overflow-visible rounded-none border-0 bg-transparent py-0 ring-0"

const panelHeaderClassName =
  "flex min-w-0 flex-1 flex-col gap-1 sm:flex-row sm:items-center "
  + "sm:justify-between"

const destructiveMessageClassName =
  "rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm "
  + "text-destructive"

const emptySearchClassName =
  "rounded-lg border p-6 text-center text-sm text-muted-foreground"

const listItemBodyClassName =
  "flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"

const searchToolbarClassName =
  "flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"

const searchIconClassName =
  "pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 "
  + "text-muted-foreground"

const resources: ResourceConfig[] = [
  {
    kind: "maintenance",
    title: "Types d’entretiens",
    description: "Catégories utilisées dans les interventions et entretiens.",
    emptyTitle: "Aucun type d’entretien",
    emptyDescription: "Créez un premier type pour structurer les entretiens.",
    deleteTitle: "Supprimer le type d’entretien ?",
    deleteDescription: (item) => (
      `${item.name} ne sera plus proposé dans les formulaires, mais restera `
      + "visible sur les entretiens existants."
    ),
    getItems: getMaintenanceTypes,
    createItem: createMaintenanceType,
    updateItem: updateMaintenanceType,
    deleteItem: deleteMaintenanceType,
  },
  {
    kind: "part",
    title: "Types de pièces",
    description: "Catégories utilisées pour organiser le stock de pièces.",
    emptyTitle: "Aucun type de pièce",
    emptyDescription: [
      "Créez un premier type pour classer les pièces du stock.",
    ].join(" "),
    deleteTitle: "Supprimer le type de pièce ?",
    deleteDescription: (item) => (
      `${item.name} sera masqué de la configuration. La suppression sera `
      + "refusée si des pièces utilisent encore ce type."
    ),
    getItems: getPartTypes,
    createItem: createPartType,
    updateItem: updatePartType,
    deleteItem: deletePartType,
  },
]

export default function ConfigurationPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Configuration</h1>
        <p className="text-sm text-muted-foreground">
          Gérez les référentiels utilisés par la GMAO.
        </p>
      </div>

      <Accordion type="multiple" className="space-y-4">
        {resources.map((resource) => (
          <ConfigurationResourcePanel key={resource.kind} resource={resource} />
        ))}
        <InspectionCentersPanel />
      </Accordion>
    </div>
  )
}

function InspectionCentersPanel() {
  const [items, setItems] = useState<InspectionCenterConfigurationItem[]>([])
  const [search, setSearch] = useState("")
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [formItem, setFormItem] = useState<
    InspectionCenterConfigurationItem | "new" | null
  >(null)
  const [deleteItem, setDeleteItem] = useState<
    InspectionCenterConfigurationItem | null
  >(null)
  const [isSaving, setIsSaving] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadItems() {
      try {
        const data = await getInspectionCentersConfiguration()

        if (!ignore) {
          setItems(data.toSorted(compareInspectionCenters))
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger les centres de contrôle technique.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadItems()

    return () => {
      ignore = true
    }
  }, [])

  const filteredItems = useMemo(() => {
    const normalizedSearch = normalize(search)

    return items.filter((item) => {
      if (!normalizedSearch) {
        return true
      }

      const searchableText = [
        item.name,
        item.phone ?? "",
        item.email ?? "",
        addressLabel(item.address),
      ].join(" ")

      return normalize(searchableText).includes(normalizedSearch)
    })
      .sort(compareInspectionCenters)
  }, [items, search])

  async function handleSave(payload: InspectionCenterConfigurationPayload) {
    if (!formItem) {
      return
    }

    setIsSaving(true)
    setError(null)

    try {
      const saved = formItem === "new"
        ? await createInspectionCenter(payload)
        : await updateInspectionCenter(formItem.id, payload)

      setItems((current) => (
        upsertById(current, saved).sort(compareInspectionCenters)
      ))
      setFormItem(null)
    } catch {
      setError(
        "Impossible d’enregistrer le centre. Vérifiez les champs saisis.",
      )
    } finally {
      setIsSaving(false)
    }
  }

  async function handleDelete() {
    if (!deleteItem) {
      return
    }

    setIsDeleting(true)
    setError(null)

    try {
      await deleteInspectionCenter(deleteItem.id)
      setItems((current) => current.filter((item) => item.id !== deleteItem.id))
      setDeleteItem(null)
    } catch (error_) {
      setError(errorMessage(
        error_,
        "Impossible de supprimer ce centre de contrôle technique.",
      ))
    } finally {
      setIsDeleting(false)
    }
  }

  function renderItemsContent() {
    return (
      <ConfigurationItemsList
        items={items}
        filteredItems={filteredItems}
        isLoading={isLoading}
        emptyTitle="Aucun centre de contrôle technique"
        emptyDescription={
          "Créez un premier centre pour le proposer dans les contrôles "
          + "techniques."
        }
        renderDetails={inspectionCenterDetails}
        onEdit={setFormItem}
        onDelete={setDeleteItem}
      />
    )
  }

  return (
    <AccordionItem value="inspection-centers">
      <AccordionTrigger>
        <div className={panelHeaderClassName}>
          <div className="min-w-0">
            <div className="font-heading text-base font-medium">
              Centres de contrôle technique
            </div>
            <p className="text-sm font-normal text-muted-foreground">
              Centres proposés lors de la saisie des contrôles techniques.
            </p>
          </div>

          <ItemCountBadge
            search={search}
            filteredCount={filteredItems.length}
            totalCount={items.length}
          />
        </div>
      </AccordionTrigger>

      <AccordionContent>
        <Card className={configurationCardClassName}>
          <CardContent className="space-y-4 px-0">
            <ConfigurationSearchToolbar
              id="inspection-center-search"
              value={search}
              placeholder="Nom, contact ou adresse..."
              onSearchChange={setSearch}
              onAdd={() => setFormItem("new")}
            />

            <PanelError
              message={error}
              hidden={Boolean(formItem || deleteItem)}
            />

            {renderItemsContent()}
          </CardContent>
        </Card>
      </AccordionContent>

      <InspectionCenterFormDialog
        key={configurationDialogKey(formItem)}
        item={formItem}
        error={formItem ? error : null}
        isSaving={isSaving}
        onOpenChange={(open) => {
          if (!open && !isSaving) {
            setFormItem(null)
          }
        }}
        onSubmit={handleSave}
      />

      <ConfirmDialog
        open={deleteItem !== null}
        title="Supprimer le centre de contrôle technique ?"
        description={deleteItem
          ? `${deleteItem.name} ne sera plus proposé dans les formulaires, `
            + "mais restera visible sur les contrôles techniques existants."
          : ""}
        error={deleteItem ? error : null}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setDeleteItem(null)
          }
        }}
        onConfirm={handleDelete}
      />
    </AccordionItem>
  )
}

function ConfigurationResourcePanel({
  resource,
}: Readonly<{ resource: ResourceConfig }>) {
  const [items, setItems] = useState<ConfigurationItem[]>([])
  const [search, setSearch] = useState("")
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [formItem, setFormItem] = useState<ConfigurationItem | null | "new">(
    null,
  )
  const [deleteItem, setDeleteItem] = useState<ConfigurationItem | null>(null)
  const [isSaving, setIsSaving] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadItems() {
      try {
        const data = await resource.getItems()

        if (!ignore) {
          setItems(data.toSorted(compareItems))
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger ces données de configuration.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadItems()

    return () => {
      ignore = true
    }
  }, [resource])

  const filteredItems = useMemo(() => {
    const normalizedSearch = normalize(search)

    return items
      .filter((item) => {
        if (!normalizedSearch) {
          return true
        }

        return normalize(
          `${item.name} ${item.description ?? ""}`,
        ).includes(normalizedSearch)
      })
      .sort(compareItems)
  }, [items, search])

  async function handleSave(payload: ConfigurationPayload) {
    if (!formItem) {
      return
    }

    setIsSaving(true)
    setError(null)

    try {
      const saved = formItem === "new"
        ? await resource.createItem(payload)
        : await resource.updateItem(formItem.id, payload)

      setItems((current) => upsertById(current, saved).sort(compareItems))
      setFormItem(null)
    } catch {
      setError("Impossible d’enregistrer. Vérifiez les champs saisis.")
    } finally {
      setIsSaving(false)
    }
  }

  async function handleDelete() {
    if (!deleteItem) {
      return
    }

    setIsDeleting(true)
    setError(null)

    try {
      await resource.deleteItem(deleteItem.id)
      setItems((current) => current.filter((item) => item.id !== deleteItem.id))
      setDeleteItem(null)
    } catch (error_) {
      setError(errorMessage(error_, "Impossible de supprimer cet élément."))
    } finally {
      setIsDeleting(false)
    }
  }

  function renderItemsContent() {
    return (
      <ConfigurationItemsList
        items={items}
        filteredItems={filteredItems}
        isLoading={isLoading}
        emptyTitle={resource.emptyTitle}
        emptyDescription={resource.emptyDescription}
        renderDetails={configurationItemDetails}
        onEdit={setFormItem}
        onDelete={setDeleteItem}
      />
    )
  }

  return (
    <AccordionItem value={resource.kind}>
      <AccordionTrigger>
        <div className={panelHeaderClassName}>
          <div className="min-w-0">
            <div className="font-heading text-base font-medium">
              {resource.title}
            </div>
            <p className="text-sm font-normal text-muted-foreground">
              {resource.description}
            </p>
          </div>

          <ItemCountBadge
            search={search}
            filteredCount={filteredItems.length}
            totalCount={items.length}
          />
        </div>
      </AccordionTrigger>

      <AccordionContent>
        <Card className={configurationCardClassName}>
          <CardContent className="space-y-4 px-0">
            <ConfigurationSearchToolbar
              id={`configuration-search-${resource.kind}`}
              value={search}
              placeholder="Nom ou description..."
              onSearchChange={setSearch}
              onAdd={() => setFormItem("new")}
            />

            <PanelError
              message={error}
              hidden={Boolean(formItem || deleteItem)}
            />

            {renderItemsContent()}
          </CardContent>
        </Card>
      </AccordionContent>

      <ConfigurationFormDialog
        key={`${resource.kind}-${configurationDialogKey(formItem)}`}
        item={formItem}
        title={
          formItem === "new"
            ? `Ajouter - ${resource.title}`
            : `Modifier - ${resource.title}`
        }
        error={formItem ? error : null}
        isSaving={isSaving}
        onOpenChange={(open) => {
          if (!open && !isSaving) {
            setFormItem(null)
          }
        }}
        onSubmit={handleSave}
      />

      <ConfirmDialog
        open={deleteItem !== null}
        title={resource.deleteTitle}
        description={deleteItem ? resource.deleteDescription(deleteItem) : ""}
        error={deleteItem ? error : null}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setDeleteItem(null)
          }
        }}
        onConfirm={handleDelete}
      />
    </AccordionItem>
  )
}

function ConfigurationFormDialog({
  item,
  title,
  error,
  isSaving,
  onOpenChange,
  onSubmit,
}: ConfigurationFormDialogProps) {
  const [name, setName] = useState(() => configurationItemName(item))
  const [description, setDescription] = useState(() => {
    return configurationItemDescription(item)
  })

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    await onSubmit({
      name,
      description: description || null,
    })
  }

  return (
    <Dialog open={item !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
            <DialogDescription>
              Renseignez le nom et la description affichés dans les
              formulaires métier.
            </DialogDescription>
          </DialogHeader>

          {error && (
            <div className={destructiveMessageClassName}>{error}</div>
          )}

          <label className="grid gap-1.5 text-sm font-medium">
            <LabelText label="Nom" required />
            <Input
              value={name}
              onChange={(event) => setName(event.target.value)}
              required
              autoFocus
            />
          </label>

          <label className="grid gap-1.5 text-sm font-medium">
            <span>Description</span>
            <Textarea
              value={description}
              onChange={(event) => setDescription(event.target.value)}
            />
          </label>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isSaving}
            >
              Annuler
            </Button>
            <Button type="submit" disabled={isSaving}>
              {isSaving ? "Enregistrement..." : "Enregistrer"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function InspectionCenterFormDialog({
  item,
  error,
  isSaving,
  onOpenChange,
  onSubmit,
}: InspectionCenterFormDialogProps) {
  const [form, setForm] = useState<InspectionCenterConfigurationPayload>(
    () => inspectionCenterForm(item),
  )

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    await onSubmit({
      name: form.name.trim(),
      phone: nullableString(formatPhone(form.phone ?? "")),
      email: nullableString(form.email),
      address: {
        line1: form.address.line1.trim(),
        line2: nullableString(form.address.line2),
        postalCode: formatPostalCode(form.address.postalCode),
        city: form.address.city.trim(),
        country: form.address.country.trim(),
      },
    })
  }

  function updateField(
    field: keyof Omit<InspectionCenterConfigurationPayload, "address">,
    value: string,
  ) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  function updateAddressField(
    field: keyof InspectionCenterConfigurationPayload["address"],
    value: string,
  ) {
    setForm((current) => ({
      ...current,
      address: { ...current.address, [field]: value },
    }))
  }

  return (
    <Dialog open={item !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <DialogHeader>
            <DialogTitle>
              {item === "new"
                ? "Ajouter - Centres de contrôle technique"
                : "Modifier - Centres de contrôle technique"}
            </DialogTitle>
            <DialogDescription>
              Renseignez le centre proposé dans les formulaires de contrôle
              technique.
            </DialogDescription>
          </DialogHeader>

          {error && (
            <div className={destructiveMessageClassName}>{error}</div>
          )}

          <div className="grid gap-4 md:grid-cols-2">
            <label className="grid gap-1.5 text-sm font-medium md:col-span-2">
              <LabelText label="Nom" required />
              <Input
                value={form.name}
                onChange={(event) => updateField("name", event.target.value)}
                required
                autoFocus
              />
            </label>

            <label className="grid gap-1.5 text-sm font-medium">
              <span>Téléphone</span>
              <Input
                value={form.phone ?? ""}
                maxLength={14}
                onChange={(event) => (
                  updateField("phone", formatPhone(event.target.value))
                )}
              />
            </label>

            <label className="grid gap-1.5 text-sm font-medium">
              <span>Email</span>
              <Input
                type="email"
                value={form.email ?? ""}
                onChange={(event) => updateField("email", event.target.value)}
              />
            </label>

            <label className="grid gap-1.5 text-sm font-medium md:col-span-2">
              <LabelText label="Adresse" required />
              <Input
                value={form.address.line1}
                onChange={(event) => (
                  updateAddressField("line1", event.target.value)
                )}
                required
              />
            </label>

            <label className="grid gap-1.5 text-sm font-medium md:col-span-2">
              <span>Complément</span>
              <Input
                value={form.address.line2 ?? ""}
                onChange={(event) => (
                  updateAddressField("line2", event.target.value)
                )}
              />
            </label>

            <label className="grid gap-1.5 text-sm font-medium">
              <LabelText label="Code postal" required />
              <Input
                value={form.address.postalCode}
                maxLength={5}
                onChange={(event) => (
                  updateAddressField(
                    "postalCode",
                    formatPostalCode(event.target.value),
                  )
                )}
                required
              />
            </label>

            <label className="grid gap-1.5 text-sm font-medium">
              <LabelText label="Ville" required />
              <Input
                value={form.address.city}
                onChange={(event) => (
                  updateAddressField("city", event.target.value)
                )}
                required
              />
            </label>

            <label className="grid gap-1.5 text-sm font-medium md:col-span-2">
              <LabelText label="Pays" required />
              <Input
                value={form.address.country}
                onChange={(event) => (
                  updateAddressField("country", event.target.value)
                )}
                required
              />
            </label>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isSaving}
            >
              Annuler
            </Button>
            <Button type="submit" disabled={isSaving}>
              {isSaving ? "Enregistrement..." : "Enregistrer"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function EmptyConfigurationState({
  title,
  description,
}: EmptyConfigurationStateProps) {
  return (
    <div className="rounded-lg border p-6 text-center">
      <div className="font-medium">{title}</div>
      <div className="mt-1 text-sm text-muted-foreground">{description}</div>
    </div>
  )
}

function ConfigurationItemsList<
  T extends { id: number; name: string; isDeleted?: boolean },
>({
  items,
  filteredItems,
  isLoading,
  emptyTitle,
  emptyDescription,
  renderDetails,
  onEdit,
  onDelete,
}: Readonly<{
  items: T[]
  filteredItems: T[]
  isLoading: boolean
  emptyTitle: string
  emptyDescription: string
  renderDetails: (item: T) => ReactNode
  onEdit: (item: T) => void
  onDelete: (item: T) => void
}>) {
  if (isLoading) {
    return <div className="text-sm text-muted-foreground">Chargement...</div>
  }

  if (items.length === 0) {
    return (
      <EmptyConfigurationState
        title={emptyTitle}
        description={emptyDescription}
      />
    )
  }

  if (filteredItems.length === 0) {
    return (
      <div className={emptySearchClassName}>
        Aucun résultat pour cette recherche.
      </div>
    )
  }

  return (
    <div className="space-y-2">
      {filteredItems.map((item) => (
        <ConfigurationListItem
          key={item.id}
          item={item}
          renderDetails={renderDetails}
          onEdit={onEdit}
          onDelete={onDelete}
        />
      ))}
    </div>
  )
}

function ConfigurationListItem<
  T extends { name: string; isDeleted?: boolean },
>({
  item,
  renderDetails,
  onEdit,
  onDelete,
}: Readonly<{
  item: T
  renderDetails: (item: T) => ReactNode
  onEdit: (item: T) => void
  onDelete: (item: T) => void
}>) {
  return (
    <div className="rounded-lg border border-foreground/10 p-3">
      <div className={listItemBodyClassName}>
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <div className="font-medium">{item.name}</div>
            <StatusBadge isDeleted={item.isDeleted} />
          </div>
          {renderDetails(item)}
        </div>

        {!item.isDeleted && (
          <ConfigurationItemActions
            onEdit={() => onEdit(item)}
            onDelete={() => onDelete(item)}
          />
        )}
      </div>
    </div>
  )
}

function inspectionCenterDetails(item: InspectionCenterConfigurationItem) {
  return (
    <>
      <p className="mt-1 text-sm text-muted-foreground">
        {addressLabel(item.address)}
      </p>
      <p className="mt-1 text-xs text-muted-foreground">
        {[item.phone, item.email].filter(Boolean).join(" - ")
          || "Aucun contact"}
      </p>
    </>
  )
}

function configurationItemDetails(item: ConfigurationItem) {
  return (
    <p className="mt-1 text-sm text-muted-foreground">
      {item.description || "—"}
    </p>
  )
}

function StatusBadge({ isDeleted }: Readonly<{ isDeleted?: boolean }>) {
  return (
    <Badge variant={isDeleted ? "outline" : "secondary"}>
      {isDeleted ? "Supprimé" : "Actif"}
    </Badge>
  )
}

function ItemCountBadge({
  search,
  filteredCount,
  totalCount,
}: ItemCountBadgeProps) {
  return (
    <Badge variant="outline" className="w-fit shrink-0">
      {search ? `${filteredCount} / ${totalCount}` : totalCount} élément(s)
    </Badge>
  )
}

function ConfigurationItemActions({
  onEdit,
  onDelete,
}: ConfigurationItemActionsProps) {
  return (
    <div className="flex shrink-0 justify-end gap-2">
      <Button variant="outline" size="sm" onClick={onEdit}>
        <Pencil />
        Modifier
      </Button>
      <Button variant="destructive" size="sm" onClick={onDelete}>
        <Trash2 />
        Supprimer
      </Button>
    </div>
  )
}

function ConfigurationSearchToolbar({
  id,
  value,
  placeholder,
  onSearchChange,
  onAdd,
}: ConfigurationSearchToolbarProps) {
  return (
    <div className={searchToolbarClassName}>
      <label className="grid flex-1 gap-1.5 text-sm font-medium" htmlFor={id}>
        <span>Recherche</span>
        <div className="relative min-w-0">
          <Search className={searchIconClassName} />
          <Input
            value={value}
            onChange={(event) => onSearchChange(event.target.value)}
            placeholder={placeholder}
            className="pl-8"
            id={id}
          />
        </div>
      </label>

      <Button onClick={onAdd}>
        <Plus />
        Ajouter
      </Button>
    </div>
  )
}

function PanelError({ message, hidden }: PanelErrorProps) {
  if (!message || hidden) {
    return null
  }

  return <div className={destructiveMessageClassName}>{message}</div>
}

function upsertById<T extends { id: number }>(items: T[], item: T) {
  const exists = items.some((current) => current.id === item.id)

  if (!exists) {
    return [...items, item]
  }

  return items.map((current) => current.id === item.id ? item : current)
}

function compareItems(first: ConfigurationItem, second: ConfigurationItem) {
  return first.name.localeCompare(second.name, "fr")
}

function compareInspectionCenters(
  first: InspectionCenterConfigurationItem,
  second: InspectionCenterConfigurationItem,
) {
  return first.name.localeCompare(second.name, "fr")
}

function addressLabel(address: InspectionCenterConfigurationItem["address"]) {
  return [
    address.line1,
    address.line2,
    address.postalCode,
    address.city,
    address.country,
  ].filter(Boolean).join(", ")
}

function configurationDialogKey(
  item: { id: number } | "new" | null,
) {
  if (!item) {
    return "closed"
  }

  return item === "new" ? "new" : String(item.id)
}

function configurationItemName(item: ConfigurationItem | "new" | null) {
  return item && item !== "new" ? item.name : ""
}

function configurationItemDescription(
  item: ConfigurationItem | "new" | null,
) {
  return item && item !== "new" ? item.description ?? "" : ""
}

function inspectionCenterForm(
  item: InspectionCenterConfigurationItem | "new" | null,
): InspectionCenterConfigurationPayload {
  if (!item || item === "new") {
    return emptyInspectionCenterForm()
  }

  return {
    name: item.name,
    phone: formatPhone(item.phone ?? ""),
    email: item.email ?? "",
    address: {
      line1: item.address.line1 ?? "",
      line2: item.address.line2 ?? "",
      postalCode: formatPostalCode(item.address.postalCode ?? ""),
      city: item.address.city ?? "",
      country: item.address.country ?? "France",
    },
  }
}

function emptyInspectionCenterForm(): InspectionCenterConfigurationPayload {
  return {
    name: "",
    phone: "",
    email: "",
    address: {
      line1: "",
      line2: "",
      postalCode: "",
      city: "",
      country: "France",
    },
  }
}

function nullableString(value: string | null | undefined) {
  const trimmedValue = value?.trim() ?? ""

  return trimmedValue === "" ? null : trimmedValue
}

function formatPhone(value: string) {
  return value
    .replaceAll(/\D/g, "")
    .slice(0, 10)
    .replaceAll(/(\d{2})(?=\d)/g, "$1 ")
}

function formatPostalCode(value: string) {
  return value.replaceAll(/\D/g, "").slice(0, 5)
}

function normalize(value: string) {
  return value
    .toLowerCase()
    .normalize("NFD")
    .replaceAll(/[\u0300-\u036f]/g, "")
}

function errorMessage(caught: unknown, fallback: string) {
  if (caught instanceof AxiosError) {
    const detail = caught.response?.data?.detail
      ?? caught.response?.data?.message

    if (typeof detail === "string") {
      return detail
    }
  }

  return fallback
}
