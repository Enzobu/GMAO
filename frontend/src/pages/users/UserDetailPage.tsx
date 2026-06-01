import { useEffect, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Info, Pencil, Trash2 } from "lucide-react"
import { AxiosError } from "axios"

import { deleteUser, getUser } from "@/api/users"
import { DocumentsPanel } from "@/components/documents-panel"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { displayValue } from "@/lib/text-format"
import { userDisplayName } from "@/lib/user-utils"
import { useAuthStore } from "@/stores/auth-store"
import type { AppUser } from "@/types/user"
import { ReadOnlyBadge, RoleBadges } from "@/pages/users/UsersPage"

const ERROR_CLASS = [
  "rounded-lg border border-destructive/30 bg-destructive/10 p-4",
  "text-sm text-destructive",
].join(" ")

const PAGE_HEADER_CLASS = [
  "flex flex-col gap-4 lg:flex-row lg:items-start",
  "lg:justify-between",
].join(" ")

const CURRENT_USER_BADGE_CLASS = [
  "rounded-4xl bg-primary px-2 py-0.5 text-xs font-medium",
  "text-primary-foreground",
].join(" ")

const EDIT_PROFILE_PATH = "/profile"

export default function UserDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const currentUser = useAuthStore((state) => state.user)
  const isAdmin = currentUser?.roles.includes("ROLE_ADMIN") ?? false
  const [user, setUser] = useState<AppUser | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isDeleteOpen, setIsDeleteOpen] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)
  const [blockedMessage, setBlockedMessage] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false

    async function load() {
      if (!id) {
        return
      }

      try {
        const data = await getUser(id)
        if (!ignore) {
          setUser(data)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger cet utilisateur.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    load()

    return () => {
      ignore = true
    }
  }, [id])

  async function confirmDelete() {
    if (!user) {
      return
    }

    setIsDeleting(true)
    setError(null)

    try {
      await deleteUser(user.id)
      navigate("/users")
    } catch (error_) {
      setError(errorMessage(error_, "Impossible de supprimer cet utilisateur."))
    } finally {
      setIsDeleting(false)
    }
  }

  function handleDeleteClick() {
    if (user?.id === currentUser?.id) {
      setBlockedMessage(
        "Vous ne pouvez pas supprimer votre propre compte administrateur.",
      )
      return
    }

    setIsDeleteOpen(true)
  }

  if (isLoading) {
    return (
      <div className="text-sm text-muted-foreground">
        Chargement de l’utilisateur...
      </div>
    )
  }

  if (error && !user) {
    return <ErrorMessage>{error}</ErrorMessage>
  }

  if (!user) {
    return null
  }

  const isCurrentUser = user.id === currentUser?.id
  const canEdit = isAdmin || isCurrentUser

  return (
    <div className="space-y-6">
      <ConfirmDialog
        open={isDeleteOpen}
        title="Supprimer l’utilisateur ?"
        description={deleteDescription(user)}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => !isDeleting && setIsDeleteOpen(open)}
        onConfirm={confirmDelete}
      />
      <InfoDialog
        message={blockedMessage}
        onOpenChange={(open) => !open && setBlockedMessage(null)}
      />

      <PageHeader
        user={user}
        canEdit={canEdit}
        isAdmin={isAdmin}
        isCurrentUser={isCurrentUser}
        onDelete={handleDeleteClick}
      />

      {error && <ErrorMessage>{error}</ErrorMessage>}

      <div className="grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Informations</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 md:grid-cols-2">
            <Metric label="Email" value={user.email} />
            <Metric label="ID" value={String(user.id)} />
            <Metric label="Prénom" value={displayValue(user.firstname)} />
            <Metric label="Nom" value={displayValue(user.lastname)} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Adresse</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 md:grid-cols-2">
            <Metric label="Adresse" value={user.address?.line1 || "—"} />
            <Metric label="Complément" value={user.address?.line2 || "—"} />
            <Metric
              label="Code postal"
              value={user.address?.postalCode || "—"}
            />
            <Metric label="Ville" value={user.address?.city || "—"} />
            <Metric label="Pays" value={user.address?.country || "—"} />
          </CardContent>
        </Card>
      </div>

      <DocumentsPanel
        parent={{ type: "users", id: user.id }}
        canManage={canEdit}
        canDelete={isAdmin}
        emptyLabel="Aucun document disponible pour cet utilisateur."
      />
    </div>
  )
}

function PageHeader({
  user,
  canEdit,
  isAdmin,
  isCurrentUser,
  onDelete,
}: Readonly<{
  user: AppUser
  canEdit: boolean
  isAdmin: boolean
  isCurrentUser: boolean
  onDelete: () => void
}>) {
  return (
    <div className={PAGE_HEADER_CLASS}>
      <div className="space-y-2">
        <h1 className="text-2xl font-semibold tracking-tight">
          {userDisplayName(user)}
        </h1>
        <div className="flex flex-wrap gap-2">
          <RoleBadges user={user} />
          {isCurrentUser && <CurrentUserBadge />}
          {!canEdit && <ReadOnlyBadge />}
        </div>
        <p className="text-sm text-muted-foreground">Utilisateur #{user.id}</p>
      </div>

      <div className="flex flex-col gap-2 sm:flex-row">
        <Button variant="outline" asChild>
          <Link to="/users">
            <ArrowLeft />
            Retour
          </Link>
        </Button>
        {canEdit && (
          <Button asChild>
            <Link to={editPath(user, isCurrentUser, isAdmin)}>
              <Pencil />
              {isCurrentUser && !isAdmin ? "Modifier mon profil" : "Modifier"}
            </Link>
          </Button>
        )}
        {isAdmin && (
          <Button variant="destructive" onClick={onDelete}>
            <Trash2 />
            Supprimer
          </Button>
        )}
      </div>
    </div>
  )
}

function CurrentUserBadge() {
  return (
    <span className="inline-flex">
      <span className={CURRENT_USER_BADGE_CLASS}>Vous</span>
    </span>
  )
}

function Metric({ label, value }: Readonly<{ label: string; value: string }>) {
  return (
    <div className="rounded-lg border p-3">
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 font-medium">{value}</div>
    </div>
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

function ErrorMessage({ children }: Readonly<{ children: string }>) {
  return (
    <div className={ERROR_CLASS}>
      {children}
    </div>
  )
}

function deleteDescription(user: AppUser) {
  return [
    `${userDisplayName(user)} sera masqué de la plateforme.`,
    "Aucune donnée ne sera supprimée définitivement.",
  ].join(" ")
}

function editPath(user: AppUser, isCurrentUser: boolean, isAdmin: boolean) {
  if (isCurrentUser && !isAdmin) {
    return EDIT_PROFILE_PATH
  }

  return `/users/${user.id}/edit`
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
