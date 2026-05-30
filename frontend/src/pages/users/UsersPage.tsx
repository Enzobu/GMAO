import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import { Info, Plus, Search, Trash2, X } from "lucide-react"
import { AxiosError } from "axios"

import { deleteUser, getUsers } from "@/api/users"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { NativeSelect } from "@/components/ui/native-select"
import { PaginationControls } from "@/components/ui/pagination-controls"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import { useAuthStore } from "@/stores/auth-store"
import type { AppUser } from "@/types/user"
import { isUserAdmin, roleLabel, userDisplayName, userInitials } from "@/lib/user-utils"

type RoleFilter = "all" | "admin" | "user"
type EditabilityFilter = "all" | "editable" | "readonly"
type SortValue = "name" | "email" | "role"
type ItemsPerPageValue = "6" | "12" | "24" | "all"

const ITEMS_PER_PAGE_OPTIONS = [
  { value: "6", label: "6" },
  { value: "12", label: "12" },
  { value: "24", label: "24" },
  { value: "all", label: "Tous" },
] as const

export default function UsersPage() {
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [users, setUsers] = useState<AppUser[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [roleFilter, setRoleFilter] = useState<RoleFilter>("all")
  const [editabilityFilter, setEditabilityFilter] = useState<EditabilityFilter>("all")
  const [sort, setSort] = useState<SortValue>("name")
  const [itemsPerPage, setItemsPerPage] = useLocalStorageState<ItemsPerPageValue>("users.itemsPerPage", "6")
  const [page, setPage] = useState(1)
  const [userToDelete, setUserToDelete] = useState<AppUser | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const [blockedMessage, setBlockedMessage] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false

    async function loadUsers() {
      try {
        const data = await getUsers()
        if (!ignore) setUsers(data)
      } catch {
        if (!ignore) setError("Impossible de charger les utilisateurs.")
      } finally {
        if (!ignore) setIsLoading(false)
      }
    }

    loadUsers()
    return () => { ignore = true }
  }, [])

  const filteredUsers = useMemo(() => {
    const normalizedSearch = normalize(search)
    return users
      .filter((user) => {
        const canEdit = canEditUser(user, currentUser?.id, isAdmin)
        const searchable = normalize([userDisplayName(user), user.email, ...user.roles.map(roleLabel)].join(" "))
        if (normalizedSearch && !searchable.includes(normalizedSearch)) return false
        if (roleFilter === "admin" && !isUserAdmin(user)) return false
        if (roleFilter === "user" && isUserAdmin(user)) return false
        if (editabilityFilter === "editable" && !canEdit) return false
        if (editabilityFilter === "readonly" && canEdit) return false
        return true
      })
      .sort((first, second) => compareUsers(first, second, sort))
  }, [users, search, roleFilter, editabilityFilter, sort, currentUser?.id, isAdmin])

  const pageSize = itemsPerPage === "all" ? filteredUsers.length || 1 : Number(itemsPerPage)
  const pageCount = Math.max(1, Math.ceil(filteredUsers.length / pageSize))
  const currentPage = Math.min(page, pageCount)
  const pageStart = (currentPage - 1) * pageSize
  const pageEnd = pageStart + pageSize
  const paginatedUsers = itemsPerPage === "all" ? filteredUsers : filteredUsers.slice(pageStart, pageEnd)
  const visibleStart = filteredUsers.length === 0 ? 0 : pageStart + 1
  const visibleEnd = itemsPerPage === "all" ? filteredUsers.length : Math.min(pageEnd, filteredUsers.length)
  const hasActiveFilters = search || roleFilter !== "all" || editabilityFilter !== "all" || sort !== "name"

  useEffect(() => { setPage(1) }, [search, roleFilter, editabilityFilter, sort, itemsPerPage])

  async function confirmDelete() {
    if (!userToDelete) return
    setIsDeleting(true)
    setError(null)
    try {
      await deleteUser(userToDelete.id)
      setUsers((current) => current.filter((user) => user.id !== userToDelete.id))
      setUserToDelete(null)
    } catch (caught) {
      setError(errorMessage(caught, "Impossible de supprimer cet utilisateur."))
    } finally {
      setIsDeleting(false)
    }
  }

  function handleDeleteClick(user: AppUser) {
    if (user.id === currentUser?.id) {
      setBlockedMessage("Vous ne pouvez pas supprimer votre propre compte administrateur.")
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

  if (isLoading) return <div className="text-sm text-muted-foreground">Chargement des utilisateurs...</div>
  if (error && users.length === 0) return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>

  return (
    <div className="space-y-6">
      <ConfirmDialog open={userToDelete !== null} title="Supprimer l’utilisateur ?" description={userToDelete ? `${userDisplayName(userToDelete)} sera masqué de la plateforme. Aucune donnée ne sera supprimée définitivement.` : ""} confirmLabel="Supprimer" isLoading={isDeleting} onOpenChange={(open) => !open && !isDeleting && setUserToDelete(null)} onConfirm={confirmDelete} />
      <InfoDialog message={blockedMessage} onOpenChange={(open) => !open && setBlockedMessage(null)} />

      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Utilisateurs</h1>
          <p className="text-sm text-muted-foreground">{filteredUsers.length} sur {users.length} utilisateur(s)</p>
        </div>
        {isAdmin && <Button asChild><Link to="/users/new"><Plus />Ajouter un utilisateur</Link></Button>}
      </div>

      {error && <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

      <Card>
        <CardContent className="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
          <label className="grid min-w-0 gap-1.5 text-sm font-medium sm:col-span-2 lg:col-span-3 xl:col-span-1" htmlFor="user-search">
            <span>Recherche</span>
            <div className="relative min-w-0"><Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Nom, email, rôle..." className="pl-8" id="user-search" /></div>
          </label>
          <NativeSelect label="Rôle" value={roleFilter} onChange={(event) => setRoleFilter(event.target.value as RoleFilter)} options={[{ value: "all", label: "Tous" }, { value: "admin", label: "Administrateurs" }, { value: "user", label: "Utilisateurs" }]} />
          <NativeSelect label="Droit" value={editabilityFilter} onChange={(event) => setEditabilityFilter(event.target.value as EditabilityFilter)} options={[{ value: "all", label: "Tous" }, { value: "editable", label: "Modifiables" }, { value: "readonly", label: "Lecture seule" }]} />
          <NativeSelect label="Tri" value={sort} onChange={(event) => setSort(event.target.value as SortValue)} options={[{ value: "name", label: "Nom A-Z" }, { value: "email", label: "Email A-Z" }, { value: "role", label: "Rôle" }]} />
          <div className="flex items-end"><Button variant="outline" className="w-full" onClick={resetFilters} disabled={!hasActiveFilters}><X />Réinitialiser</Button></div>
        </CardContent>
      </Card>

      {filteredUsers.length === 0 ? <Card><CardContent className="py-8 text-center text-sm text-muted-foreground">Aucun utilisateur ne correspond aux critères.</CardContent></Card> : (
        <>
          <PaginationControls currentPage={currentPage} pageCount={pageCount} totalItems={filteredUsers.length} visibleStart={visibleStart} visibleEnd={visibleEnd} itemsPerPage={itemsPerPage} itemsPerPageOptions={ITEMS_PER_PAGE_OPTIONS} onItemsPerPageChange={(value) => setItemsPerPage(value as ItemsPerPageValue)} onPreviousPage={() => setPage((current) => Math.max(1, current - 1))} onNextPage={() => setPage((current) => Math.min(pageCount, current + 1))} itemLabel="utilisateur(s)" />
          <div className="grid gap-4 xl:grid-cols-2">
            {paginatedUsers.map((user) => {
              const canEdit = canEditUser(user, currentUser?.id, isAdmin)
              const isCurrentUser = user.id === currentUser?.id
              return (
                <Card key={user.id} className="relative border border-foreground/10 ring-0 transition-colors hover:border-primary/35 hover:bg-muted/30">
                  <Link to={`/users/${user.id}`} className="absolute inset-0 z-10 rounded-xl focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-ring/50" aria-label={`Voir ${userDisplayName(user)}`} />
                  <CardHeader><CardTitle className="flex flex-wrap items-center gap-2"><Avatar user={user} /><span>{userDisplayName(user)}</span><RoleBadges user={user} />{isCurrentUser && <Badge>Vous</Badge>}{!canEdit && <ReadOnlyBadge />}</CardTitle></CardHeader>
                  <CardContent><div className="text-sm text-muted-foreground"><strong className="text-foreground">Email</strong> {user.email}</div></CardContent>
                  {(canEdit || isAdmin) && <CardFooter className="relative z-20 justify-end gap-2">{canEdit && <Button variant="outline" size="sm" asChild><Link to={isCurrentUser && !isAdmin ? "/profile" : `/users/${user.id}/edit`}>Modifier</Link></Button>}{isAdmin && <Button variant="destructive" size="sm" onClick={() => handleDeleteClick(user)}><Trash2 />Supprimer</Button>}</CardFooter>}
                </Card>
              )
            })}
          </div>
          {pageCount > 1 && <PaginationControls currentPage={currentPage} pageCount={pageCount} totalItems={filteredUsers.length} visibleStart={visibleStart} visibleEnd={visibleEnd} itemsPerPage={itemsPerPage} itemsPerPageOptions={ITEMS_PER_PAGE_OPTIONS} onItemsPerPageChange={(value) => setItemsPerPage(value as ItemsPerPageValue)} onPreviousPage={() => setPage((current) => Math.max(1, current - 1))} onNextPage={() => setPage((current) => Math.min(pageCount, current + 1))} itemLabel="utilisateur(s)" />}
        </>
      )}
    </div>
  )
}

export function RoleBadges({ user }: Readonly<{ user: AppUser }>) {
  return <>{user.roles.map((role) => <Badge key={role} variant={role === "ROLE_ADMIN" ? "destructive" : "secondary"}>{roleLabel(role)}</Badge>)}</>
}

export function ReadOnlyBadge() {
  return <Badge variant="outline" className="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300">Lecture seule</Badge>
}

function Avatar({ user }: Readonly<{ user: AppUser }>) {
  return <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-xs font-semibold text-primary-foreground">{userInitials(user)}</span>
}

function InfoDialog({ message, onOpenChange }: Readonly<{ message: string | null; onOpenChange: (open: boolean) => void }>) {
  return <Dialog open={message !== null} onOpenChange={onOpenChange}><DialogContent><DialogHeader><DialogTitle className="flex items-center gap-2"><Info className="size-5" />Action impossible</DialogTitle><DialogDescription>{message}</DialogDescription></DialogHeader><DialogFooter><Button onClick={() => onOpenChange(false)}>Compris</Button></DialogFooter></DialogContent></Dialog>
}

function canEditUser(user: AppUser, currentUserId: number | undefined, isAdmin: boolean) {
  return isAdmin || user.id === currentUserId
}

function compareUsers(first: AppUser, second: AppUser, sort: SortValue) {
  if (sort === "email") return first.email.localeCompare(second.email, "fr")
  if (sort === "role") return Number(isUserAdmin(second)) - Number(isUserAdmin(first))
  return userDisplayName(first).localeCompare(userDisplayName(second), "fr")
}

function normalize(value: string) {
  return value.toLowerCase().normalize("NFD").replaceAll(/[\u0300-\u036f]/g, "")
}

function errorMessage(caught: unknown, fallback: string) {
  if (caught instanceof AxiosError) {
    const detail = caught.response?.data?.detail ?? caught.response?.data?.message
    if (typeof detail === "string") return detail
  }
  return fallback
}
