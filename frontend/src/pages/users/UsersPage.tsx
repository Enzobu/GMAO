import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Info, Trash2 } from "lucide-react"
import { AxiosError } from "axios"

import { emptyCollectionPage } from "@/api/api-collection"
import { deleteUser, getUsersPage } from "@/api/users"
import {
  CARD_LINK_CLASS,
  FILTER_GRID_CLASS,
  RESOURCE_CARD_CLASS,
} from "@/components/list-page-classes"
import {
  EmptyListCard,
  ListPageHeader,
  ReadOnlyBadge,
  ResetFiltersButton,
  SearchField,
} from "@/components/list-page-primitives"
import {
  itemsPerPageSize,
  type ItemsPerPageValue,
} from "@/components/list-page-pagination"
import { ListPaginationControls } from "@/components/list-pagination-controls"
import { ErrorMessage } from "@/components/page-primitives"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { NativeSelect } from "@/components/ui/native-select"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import {
  roleLabel,
  userDisplayName,
  userInitials,
} from "@/lib/user-utils"
import { useAuthStore } from "@/stores/auth-store"
import type { AppUser } from "@/types/user"

type RoleFilter = "all" | "admin" | "user"
type EditabilityFilter = "all" | "editable" | "readonly"
type SortValue = "name" | "email" | "role"

const AVATAR_CLASS = [
  "flex size-8 items-center justify-center rounded-lg bg-primary",
  "text-xs font-semibold text-primary-foreground",
].join(" ")

export default function UsersPage() {
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [users, setUsers] = useState<AppUser[]>([])
  const [usersPage, setUsersPage] = useState(emptyCollectionPage<AppUser>(6))
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [roleFilter, setRoleFilter] = useState<RoleFilter>("all")
  const [editabilityFilter, setEditabilityFilter] =
    useState<EditabilityFilter>("all")
  const [sort, setSort] = useState<SortValue>("name")
  const [itemsPerPage, setItemsPerPage] =
    useLocalStorageState<ItemsPerPageValue>("users.itemsPerPage", "6")
  const [page, setPage] = useState(1)
  const [userToDelete, setUserToDelete] = useState<AppUser | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const [blockedMessage, setBlockedMessage] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false

    async function loadUsers() {
      try {
        const data = await getUsersPage({
          page,
          itemsPerPage: itemsPerPageSize(itemsPerPage),
          search,
          role: roleFilter,
          editability: editabilityFilter,
          sort,
        })
        if (!ignore) {
          setUsers(data.items)
          setUsersPage(data)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger les utilisateurs.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadUsers()

    return () => {
      ignore = true
    }
  }, [editabilityFilter, itemsPerPage, page, roleFilter, search, sort])

  const hasActiveFilters = Boolean(
    search
      || roleFilter !== "all"
      || editabilityFilter !== "all"
      || sort !== "name",
  )

  async function confirmDelete() {
    if (!userToDelete) {
      return
    }

    setIsDeleting(true)
    setError(null)

    try {
      await deleteUser(userToDelete.id)
      setUsers((current) => {
        return current.filter((user) => user.id !== userToDelete.id)
      })
      setUsersPage((current) => ({
        ...current,
        totalItems: Math.max(0, current.totalItems - 1),
      }))
      setUserToDelete(null)
    } catch (error_) {
      setError(errorMessage(error_, "Impossible de supprimer cet utilisateur."))
    } finally {
      setIsDeleting(false)
    }
  }

  function handleDeleteClick(user: AppUser) {
    if (user.id === currentUser?.id) {
      setBlockedMessage(
        "Vous ne pouvez pas supprimer votre propre compte administrateur.",
      )
      return
    }

    setUserToDelete(user)
  }

  function resetFilters() {
    setSearch("")
    setRoleFilter("all")
    setEditabilityFilter("all")
    setSort("name")
  }

  if (isLoading) {
    return (
      <div className="text-sm text-muted-foreground">
        Chargement des utilisateurs...
      </div>
    )
  }

  if (error && users.length === 0) {
    return <ErrorMessage>{error}</ErrorMessage>
  }

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={userToDelete !== null}
        title="Supprimer l’utilisateur ?"
        description={userToDelete ? deleteDescription(userToDelete) : ""}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setUserToDelete(null)
          }
        }}
        onConfirm={confirmDelete}
      />
      <InfoDialog
        message={blockedMessage}
        onOpenChange={(open) => !open && setBlockedMessage(null)}
      />

      <ListPageHeader
        title="Utilisateurs"
        countLabel={`${usersPage.totalItems} utilisateur(s)`}
        addTo={isAdmin ? "/users/new" : undefined}
        addLabel="Ajouter un utilisateur"
      />

      {error && <ErrorMessage>{error}</ErrorMessage>}

      <FiltersCard
        search={search}
        roleFilter={roleFilter}
        editabilityFilter={editabilityFilter}
        sort={sort}
        hasActiveFilters={hasActiveFilters}
        onSearchChange={(value) => updateFilter(setSearch, value, setPage)}
        onRoleChange={(value) => updateFilter(setRoleFilter, value, setPage)}
        onEditabilityChange={(value) => {
          updateFilter(setEditabilityFilter, value, setPage)
        }}
        onSortChange={(value) => updateFilter(setSort, value, setPage)}
        onReset={resetFilters}
      />

      {users.length === 0 ? (
        <EmptyListCard>
          Aucun utilisateur ne correspond aux critères.
        </EmptyListCard>
      ) : (
        <>
          <UsersPagination
            pagination={usersPage}
            itemsPerPage={itemsPerPage}
            onItemsPerPageChange={(value) => {
              setItemsPerPage(value)
              setPage(1)
            }}
            onPageChange={setPage}
          />
          <div className="grid gap-4 xl:grid-cols-2">
            {users.map((user) => (
              <UserCard
                key={user.id}
                user={user}
                currentUserId={currentUser?.id}
                isAdmin={isAdmin}
                onDelete={handleDeleteClick}
              />
            ))}
          </div>
          <UsersPagination
            pagination={usersPage}
            itemsPerPage={itemsPerPage}
            onItemsPerPageChange={(value) => {
              setItemsPerPage(value)
              setPage(1)
            }}
            onPageChange={setPage}
          />
        </>
      )}
    </div>
  )
}

function FiltersCard({
  search,
  roleFilter,
  editabilityFilter,
  sort,
  hasActiveFilters,
  onSearchChange,
  onRoleChange,
  onEditabilityChange,
  onSortChange,
  onReset,
}: Readonly<{
  search: string
  roleFilter: RoleFilter
  editabilityFilter: EditabilityFilter
  sort: SortValue
  hasActiveFilters: boolean
  onSearchChange: (value: string) => void
  onRoleChange: (value: RoleFilter) => void
  onEditabilityChange: (value: EditabilityFilter) => void
  onSortChange: (value: SortValue) => void
  onReset: () => void
}>) {
  return (
    <Card>
      <CardContent className={FILTER_GRID_CLASS}>
        <SearchField
          id="user-search"
          value={search}
          placeholder="Nom, email, rôle..."
          onChange={onSearchChange}
        />
        <NativeSelect
          label="Rôle"
          value={roleFilter}
          onChange={(event) => onRoleChange(event.target.value as RoleFilter)}
          options={ROLE_FILTER_OPTIONS}
        />
        <NativeSelect
          label="Droit"
          value={editabilityFilter}
          onChange={(event) => {
            onEditabilityChange(event.target.value as EditabilityFilter)
          }}
          options={EDITABILITY_FILTER_OPTIONS}
        />
        <NativeSelect
          label="Tri"
          value={sort}
          onChange={(event) => onSortChange(event.target.value as SortValue)}
          options={SORT_OPTIONS}
        />
        <ResetFiltersButton
          disabled={!hasActiveFilters}
          onReset={onReset}
        />
      </CardContent>
    </Card>
  )
}

function UsersPagination(props: Readonly<{
  pagination: Parameters<typeof ListPaginationControls>[0]["pagination"]
  itemsPerPage: ItemsPerPageValue
  onItemsPerPageChange: (value: ItemsPerPageValue) => void
  onPageChange: (value: number) => void
}>) {
  return <ListPaginationControls {...props} itemLabel="utilisateur(s)" />
}

function UserCard({
  user,
  currentUserId,
  isAdmin,
  onDelete,
}: Readonly<{
  user: AppUser
  currentUserId?: number
  isAdmin: boolean
  onDelete: (user: AppUser) => void
}>) {
  const canEdit = canEditUser(user, currentUserId, isAdmin)
  const isCurrentUser = user.id === currentUserId

  return (
    <Card className={RESOURCE_CARD_CLASS}>
      <Link
        to={`/users/${user.id}`}
        className={CARD_LINK_CLASS}
        aria-label={`Voir ${userDisplayName(user)}`}
      />
      <CardHeader>
        <CardTitle className="flex flex-wrap items-center gap-2">
          <Avatar user={user} />
          <span>{userDisplayName(user)}</span>
          <RoleBadges user={user} />
          {isCurrentUser && <Badge>Vous</Badge>}
          {!canEdit && <ReadOnlyBadge />}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="text-sm text-muted-foreground">
          <strong className="text-foreground">Email</strong> {user.email}
        </div>
      </CardContent>
      {(canEdit || isAdmin) && (
        <CardFooter className="relative z-20 justify-end gap-2">
          {canEdit && (
            <Button variant="outline" size="sm" asChild>
              <Link to={editPath(user, isCurrentUser, isAdmin)}>Modifier</Link>
            </Button>
          )}
          {isAdmin && (
            <Button
              variant="destructive"
              size="sm"
              onClick={() => onDelete(user)}
            >
              <Trash2 />
              Supprimer
            </Button>
          )}
        </CardFooter>
      )}
    </Card>
  )
}

export function RoleBadges({ user }: Readonly<{ user: AppUser }>) {
  return (
    <>
      {user.roles.map((role) => (
        <Badge
          key={role}
          variant={role === "ROLE_ADMIN" ? "destructive" : "secondary"}
        >
          {roleLabel(role)}
        </Badge>
      ))}
    </>
  )
}

function Avatar({ user }: Readonly<{ user: AppUser }>) {
  return (
    <span className={AVATAR_CLASS}>
      {userInitials(user)}
    </span>
  )
}

function InfoDialog({
  message,
  onOpenChange,
}: Readonly<{
  message: string | null
  onOpenChange: (open: boolean) => void
}>) {
  return (
    <Dialog open={message !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Info className="size-5" />
            Action impossible
          </DialogTitle>
          <DialogDescription>{message}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button onClick={() => onOpenChange(false)}>Compris</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

function canEditUser(
  user: AppUser,
  currentUserId: number | undefined,
  isAdmin: boolean,
) {
  return isAdmin || user.id === currentUserId
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

function deleteDescription(user: AppUser) {
  return [
    `${userDisplayName(user)} sera masqué de la plateforme.`,
    "Aucune donnée ne sera supprimée définitivement.",
  ].join(" ")
}

function editPath(user: AppUser, isCurrentUser: boolean, isAdmin: boolean) {
  return isCurrentUser && !isAdmin ? "/profile" : `/users/${user.id}/edit`
}

const ROLE_FILTER_OPTIONS = [
  { value: "all", label: "Tous" },
  { value: "admin", label: "Administrateurs" },
  { value: "user", label: "Utilisateurs" },
] as const

const EDITABILITY_FILTER_OPTIONS = [
  { value: "all", label: "Tous" },
  { value: "editable", label: "Modifiables" },
  { value: "readonly", label: "Lecture seule" },
] as const

const SORT_OPTIONS = [
  { value: "name", label: "Nom A-Z" },
  { value: "email", label: "Email A-Z" },
  { value: "role", label: "Rôle" },
] as const

function updateFilter<T>(
  setter: (value: T) => void,
  value: T,
  setPage: (value: number) => void,
) {
  setter(value)
  setPage(1)
}
