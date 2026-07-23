import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-barre-navigation',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <header class="fixed top-0 left-0 right-0 w-full z-50 bg-white/5 dark:bg-white/5 backdrop-blur-md text-black font-sans border-b border-white/20 shadow-sm transition-all duration-300">
      <div class="flex justify-between items-center px-4 md:px-10 py-4 max-w-7xl mx-auto">
        <a routerLink="/" class="flex items-center gap-2 group">
          <div class="w-9 h-9 rounded-xl bg-black text-white flex items-center justify-center font-black text-xl shadow-md group-hover:scale-105 transition-transform duration-200">
            <mat-icon class="text-xl leading-none">play_arrow</mat-icon>
          </div>
          <span class="text-2xl font-black tracking-tight text-black">
            MicMediaFetch
          </span>
        </a>

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
      </div>
    </header>
  `
})
export class ComposantBarreNavigation {}
