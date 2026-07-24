import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-barre-navigation',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <header class="fixed top-0 left-0 right-0 w-full z-50 bg-white/90 dark:bg-white/90 backdrop-blur-md text-black font-sans border-b border-white/20 shadow-sm transition-all duration-300">
      <div class="flex justify-between items-center px-4 md:px-10 py-4 max-w-7xl mx-auto">
        <a routerLink="/" (click)="fermerMenuMobile()" class="flex items-center gap-2 group">
          <div class="w-9 h-9 rounded-xl bg-black text-white flex items-center justify-center font-black text-xl shadow-md group-hover:scale-105 transition-transform duration-200">
            <mat-icon class="text-xl leading-none">play_arrow</mat-icon>
          </div>
          <span class="text-xl sm:text-2xl font-black tracking-tight text-black">
            MicMediaFetch
          </span>
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex gap-8 items-center font-medium text-sm">
          <a routerLink="/comment-utiliser" 
             routerLinkActive="text-black font-bold border-b-2 border-black pb-1"
             [routerLinkActiveOptions]="{exact: true}"
             class="text-black hover:opacity-80 transition-opacity duration-200 pb-1 border-b-2 border-transparent">
            Comment utiliser
          </a>
          <a routerLink="/sites-supportes" 
             routerLinkActive="text-black font-bold border-b-2 border-black pb-1"
             class="text-black hover:opacity-80 transition-opacity duration-200 pb-1 border-b-2 border-transparent">
            Sites supportés
          </a>
          <a routerLink="/faq" 
             routerLinkActive="text-black font-bold border-b-2 border-black pb-1"
             class="text-black hover:opacity-80 transition-opacity duration-200 pb-1 border-b-2 border-transparent">
            FAQ
          </a>
          <a routerLink="/historique" 
             routerLinkActive="text-black font-bold border-b-2 border-black pb-1"
             class="text-black hover:opacity-80 transition-opacity duration-200 pb-1 border-b-2 border-transparent">
            Historique
          </a>
        </nav>

        <!-- Mobile Menu Toggle Button -->
        <button (click)="basculerMenuMobile()"
                type="button"
                [attr.aria-label]="estMenuMobileOuvert() ? 'Fermer le menu' : 'Ouvrir le menu'"
                class="md:hidden p-2 rounded-xl text-black hover:bg-black/5 active:bg-black/10 transition-colors flex items-center justify-center">
          <mat-icon class="text-2xl leading-none">
            {{ estMenuMobileOuvert() ? 'close' : 'menu' }}
          </mat-icon>
        </button>
      </div>

      <!-- Mobile Dropdown Navigation Drawer -->
      @if (estMenuMobileOuvert()) {
        <div class="md:hidden bg-white dark:bg-white border-t border-black/10 px-4 py-4 flex flex-col gap-2 shadow-xl animate-in slide-in-from-top duration-200">
          <a routerLink="/comment-utiliser"
             (click)="fermerMenuMobile()"
             routerLinkActive="bg-black/10 font-bold"
             [routerLinkActiveOptions]="{exact: true}"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-black font-semibold text-base hover:bg-black/5 transition-colors">
            <mat-icon class="text-xl">help_outline</mat-icon>
            <span>Comment utiliser</span>
          </a>

          <a routerLink="/sites-supportes"
             (click)="fermerMenuMobile()"
             routerLinkActive="bg-black/10 font-bold"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-black font-semibold text-base hover:bg-black/5 transition-colors">
            <mat-icon class="text-xl">language</mat-icon>
            <span>Sites supportés</span>
          </a>

          <a routerLink="/faq"
             (click)="fermerMenuMobile()"
             routerLinkActive="bg-black/10 font-bold"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-black font-semibold text-base hover:bg-black/5 transition-colors">
            <mat-icon class="text-xl">quiz</mat-icon>
            <span>FAQ</span>
          </a>

          <a routerLink="/historique"
             (click)="fermerMenuMobile()"
             routerLinkActive="bg-black/10 font-bold"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-black font-semibold text-base hover:bg-black/5 transition-colors">
            <mat-icon class="text-xl">history</mat-icon>
            <span>Historique</span>
          </a>
        </div>
      }
    </header>
  `
})
export class ComposantBarreNavigation {
  public estMenuMobileOuvert = signal<boolean>(false);

  public basculerMenuMobile(): void {
    this.estMenuMobileOuvert.update(etat => !etat);
  }

  public fermerMenuMobile(): void {
    this.estMenuMobileOuvert.set(false);
  }
}

