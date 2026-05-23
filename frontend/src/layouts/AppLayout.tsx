import {
  Bell,
  CarFront,
  FileText,
  LayoutDashboard,
  Settings,
  Shield,
  Wrench,
  LogOut,
} from "lucide-react"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { NavLink, Outlet } from "react-router-dom"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { useAuthStore } from "@/stores/auth-store"

const navigation = [
  {
    label: "Dashboard",
    icon: LayoutDashboard,
    path: "/dashboard",
  },
  {
    label: "Véhicules",
    icon: CarFront,
    path: "/vehicles",
  },
  {
    label: "Entretiens",
    icon: Wrench,
    path: "/maintenances",
  },
  {
    label: "Assurances",
    icon: Shield,
    path: "/insurances",
  },
  {
    label: "Documents",
    icon: FileText,
    path: "/documents",
  },
]

export default function AppLayout() {
  const user = useAuthStore((state) => state.user)

  return (
    <div className="min-h-screen bg-background text-foreground">
      <div className="flex">
        {/* Sidebar */}
        <aside className="hidden h-screen w-72 shrink-0 border-r border-border bg-card lg:block">
          <div className="flex h-full flex-col">
            {/* Logo */}
            <div className="border-b border-border p-6">
              <div className="flex items-center gap-4">
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg">
                  <Wrench className="h-6 w-6" />
                </div>

                <div>
                  <h1 className="text-lg font-semibold">
                    GMAO Fleet
                  </h1>

                  <p className="text-sm text-muted-foreground">
                    Gestion de parc
                  </p>
                </div>
              </div>
            </div>

            {/* Navigation */}
            <nav className="flex-1 space-y-2 p-4">
              {navigation.map((item) => {
                const Icon = item.icon

                return (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    className={({ isActive }) =>
                      [
                        "flex h-12 items-center gap-3 rounded-xl px-4 text-sm font-medium transition-all",
                        isActive
                          ? "bg-primary text-primary-foreground shadow-md"
                          : "text-muted-foreground hover:bg-muted hover:text-foreground",
                      ].join(" ")
                    }
                  >
                    <Icon className="h-4 w-4" />
                    {item.label}
                  </NavLink>
                )
              })}
            </nav>

            {/* Footer */}
            <div className="border-t border-border p-4">
              <Button
                variant="ghost"
                className="h-12 w-full justify-start rounded-xl"
              >
                <Settings className="mr-3 h-4 w-4" />
                Paramètres
              </Button>
            </div>
          </div>
        </aside>

        {/* Main */}
        <div className="flex min-h-screen flex-1 flex-col">
          {/* Header */}
          <header className="sticky top-0 z-40 border-b border-border bg-background/80 backdrop-blur">
            <div className="flex h-20 items-center justify-between px-8">
              {/* Search */}
              <div className="w-full max-w-lg">
                <Input
                  placeholder="Rechercher..."
                  className="h-12 rounded-xl border-border bg-card"
                />
              </div>

              {/* Right */}
              <div className="flex items-center gap-4">
                <Button
                  variant="outline"
                  size="icon"
                  className="h-11 w-11 rounded-xl"
                >
                  <Bell className="h-4 w-4" />
                </Button>

                <Button
                  variant="outline"
                  onClick={() => {
                    localStorage.removeItem("token")
                    window.location.href = "/login"
                  }}
                >
                  Déconnexion
                </Button>

                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <button className="flex items-center gap-3 rounded-xl border border-border bg-card px-4 py-2 shadow-sm transition hover:bg-muted/40">
                      <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary font-semibold text-primary-foreground">
                        {user?.initials ?? "?"}
                      </div>

                      <div className="text-left">
                        <p className="text-sm font-medium">
                          {user?.displayName ?? user?.email ?? "Utilisateur"}
                        </p>

                        <p className="text-xs text-muted-foreground">
                          {user?.roles?.some((role) => role === "ROLE_ADMIN")
                            ? "Administrateur"
                            : "Utilisateur"}
                        </p>
                      </div>
                    </button>
                  </DropdownMenuTrigger>

                  <DropdownMenuContent
                    align="end"
                    className="w-56 rounded-m p-2"
                  >
                    <DropdownMenuItem className="cursor-pointer">
                      <Settings className="mr-2 h-4 w-4" />
                      Paramètres du profil
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                      className="cursor-pointer text-red-500 focus:text-red-500"
                      onClick={() => {
                        logout()
                        window.location.href = "/login"
                      }}
                    >
                      <LogOut className="mr-2 h-4 w-4" />
                      Déconnexion
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>
          </header>

          {/* Content */}
          <main className="flex-1 p-8">
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  )
}