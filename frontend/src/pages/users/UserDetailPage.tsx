import { useEffect, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { ArrowLeft, Info, Pencil, Trash2 } from "lucide-react"
import { AxiosError } from "axios"

import { deleteUser, getUser } from "@/api/users"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { useAuthStore } from "@/stores/auth-store"
import type { AppUser } from "@/types/user"
import { userDisplayName } from "@/lib/user-utils"
import { ReadOnlyBadge, RoleBadges } from "@/pages/users/UsersPage"

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
      if (!id) return
      try {
        const data = await getUser(id)
        if (!ignore) setUser(data)
      } catch {
        if (!ignore) setError("Impossible de charger cet utilisateur.")
      } finally {
        if (!ignore) setIsLoading(false)
      }
    }
    load()
    return () => { ignore = true }
  }, [id])

  async function confirmDelete() {
    if (!user) return
    setIsDeleting(true)
    setError(null)
    try {
      await deleteUser(user.id)
      navigate("/users")
    } catch (caught) {
      setError(errorMessage(caught, "Impossible de supprimer cet utilisateur."))
    } finally {
      setIsDeleting(false)
    }
  }

  function handleDeleteClick() {
    if (user?.id === currentUser?.id) {
      setBlockedMessage("Vous ne pouvez pas supprimer votre propre compte administrateur.")
      return
    }
    setIsDeleteOpen(true)
  }

  if (isLoading) return <div className="text-sm text-muted-foreground">Chargement de l’utilisateur...</div>
  if (error && !user) return <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>
  if (!user) return null

  const isCurrentUser = user.id === currentUser?.id
  const canEdit = isAdmin || isCurrentUser

  return (
    <div className="space-y-6">
      <ConfirmDialog open={isDeleteOpen} title="Supprimer l’utilisateur ?" description={`${userDisplayName(user)} sera masqué de la plateforme. Aucune donnée ne sera supprimée définitivement.`} confirmLabel="Supprimer" isLoading={isDeleting} onOpenChange={(open) => !isDeleting && setIsDeleteOpen(open)} onConfirm={confirmDelete} />
      <InfoDialog message={blockedMessage} onOpenChange={(open) => !open && setBlockedMessage(null)} />

      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div className="space-y-2"><h1 className="text-2xl font-semibold tracking-tight">{userDisplayName(user)}</h1><div className="flex flex-wrap gap-2"><RoleBadges user={user} />{isCurrentUser && <span className="inline-flex"><span className="rounded-4xl bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground">Vous</span></span>}{!canEdit && <ReadOnlyBadge />}</div><p className="text-sm text-muted-foreground">Utilisateur #{user.id}</p></div>
        <div className="flex flex-col gap-2 sm:flex-row"><Button variant="outline" asChild><Link to="/users"><ArrowLeft />Retour</Link></Button>{canEdit && <Button asChild><Link to={isCurrentUser && !isAdmin ? "/profile" : `/users/${user.id}/edit`}><Pencil />{isCurrentUser && !isAdmin ? "Modifier mon profil" : "Modifier"}</Link></Button>}{isAdmin && <Button variant="destructive" onClick={handleDeleteClick}><Trash2 />Supprimer</Button>}</div>
      </div>

      {error && <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

      <div className="grid gap-4 xl:grid-cols-2">
        <Card><CardHeader><CardTitle>Informations</CardTitle></CardHeader><CardContent className="grid gap-3 md:grid-cols-2"><Metric label="Email" value={user.email} /><Metric label="ID" value={String(user.id)} /><Metric label="Prénom" value={user.firstname || "—"} /><Metric label="Nom" value={user.lastname || "—"} /></CardContent></Card>
        <Card><CardHeader><CardTitle>Adresse</CardTitle></CardHeader><CardContent className="grid gap-3 md:grid-cols-2"><Metric label="Adresse" value={user.address?.line1 || "—"} /><Metric label="Complément" value={user.address?.line2 || "—"} /><Metric label="Code postal" value={user.address?.postalCode || "—"} /><Metric label="Ville" value={user.address?.city || "—"} /><Metric label="Pays" value={user.address?.country || "—"} /></CardContent></Card>
      </div>
    </div>
  )
}

function Metric({ label, value }: Readonly<{ label: string; value: string }>) {
  return <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">{label}</div><div className="mt-1 font-medium">{value}</div></div>
}

function InfoDialog({ message, onOpenChange }: Readonly<{ message: string | null; onOpenChange: (open: boolean) => void }>) {
  return <Dialog open={message !== null} onOpenChange={onOpenChange}><DialogContent><DialogHeader><DialogTitle className="flex items-center gap-2"><Info className="size-5" />Action impossible</DialogTitle><DialogDescription>{message}</DialogDescription></DialogHeader><DialogFooter><Button onClick={() => onOpenChange(false)}>Compris</Button></DialogFooter></DialogContent></Dialog>
}

function errorMessage(caught: unknown, fallback: string) {
  if (caught instanceof AxiosError) {
    const detail = caught.response?.data?.detail ?? caught.response?.data?.message
    if (typeof detail === "string") return detail
  }
  return fallback
}
