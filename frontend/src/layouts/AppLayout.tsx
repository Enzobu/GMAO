import { useEffect, useRef } from "react"
import {
  Bell,
  CarFront,
  House,
  Settings,
  User,
  Warehouse,
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
import { ThemeToggle } from "@/components/theme-toggle"
import { useAuthStore } from "@/stores/auth-store"

const navigation = [
  {
    label: "Accueil",
    icon: House,
    path: "/dashboard",
  },
  {
    label: "Véhicules",
    icon: CarFront,
    path: "/vehicles",
  },
  {
    label: "Interventions",
    icon: Wrench,
    path: "/interventions",
  },
  {
    label: "Stock",
    icon: Warehouse,
    path: "/parts",
  },
  {
    label: "Utilisateurs",
    icon: User,
    path: "/users",
  },
]

export default function AppLayout() {
  const searchInputRef = useRef<HTMLInputElement>(null)
  const user = useAuthStore((state) => state.user)
  const logout = useAuthStore((state) => state.logout)
  const isAdmin = user?.roles.some((role) => role === "ROLE_ADMIN") ?? false

  useEffect(() => {
    function handleSearchShortcut(event: KeyboardEvent) {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
        event.preventDefault()
        searchInputRef.current?.focus()
      }
    }

    window.addEventListener("keydown", handleSearchShortcut)

    return () => window.removeEventListener("keydown", handleSearchShortcut)
  }, [])

  return (
    <div className="min-h-screen bg-background text-foreground">
      <div className="flex">
        {/* Sidebar */}
        <aside className="fixed inset-y-0 left-0 z-50 hidden w-72 border-r border-border bg-card lg:block">
          <div className="flex h-full flex-col">
            {/* Logo */}
            <div className="border-b border-border p-6">
              <div className="flex items-center gap-4">
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg">
                  <Wrench className="h-6 w-6" />
                </div>

                <div>
                  <h1 className="text-lg font-semibold">
                    GMAO
                  </h1>

                  <p className="text-sm text-muted-foreground">
                    Gestion d'entretien
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
                asChild
              >
                <NavLink to="/settings">
                  <Settings className="mr-3 h-4 w-4" />
                  Paramètres
                </NavLink>
              </Button>

              {isAdmin && (
                <Button
                  variant="ghost"
                  className="mt-2 h-12 w-full justify-start rounded-xl"
                  asChild
                >
                  <NavLink to="/configuration">
                    <Settings className="mr-3 h-4 w-4" />
                    Configuration
                  </NavLink>
                </Button>
              )}
            </div>
          </div>
        </aside>

        {/* Main */}
        <div className="flex min-h-screen flex-1 flex-col lg:pl-72">
          {/* Header */}
          <header className="sticky top-0 z-40 border-b border-border bg-background/80 backdrop-blur">
            <div className="flex h-20 items-center justify-between px-8">
              {/* Search */}
              <div className="relative w-full max-w-lg">
                <Input
                  ref={searchInputRef}
                  placeholder="Rechercher..."
                  className="h-12 rounded-xl border-border bg-card pr-20"
                />

                <div className="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 items-center gap-1 text-xs text-muted-foreground sm:flex">
                  <kbd className="rounded-md border border-border bg-muted px-1.5 py-0.5 font-sans">
                    Ctrl
                  </kbd>
                  <kbd className="rounded-md border border-border bg-muted px-1.5 py-0.5 font-sans">
                    K
                  </kbd>
                </div>
              </div>

              {/* Right */}
              <div className="flex items-center gap-4">
                <ThemeToggle />

                <Button
                  variant="outline"
                  size="icon"
                  className="h-11 w-11 rounded-xl"
                >
                  <Bell className="h-4 w-4" />
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
                      <NavLink to="/profile" className="flex w-full items-center">
                        <Settings className="mr-2 h-4 w-4" />
                        Paramètres du profil
                      </NavLink>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                      variant="destructive"
                      className="cursor-pointer text-red-500 focus:text-red-500 [&_svg]:text-red-500"
                      onClick={() => {
                        logout()
                        window.location.href = "/login"
                      }}
                    >
                      <LogOut className="mr-2 h-4 w-4 text-red-500" />
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
