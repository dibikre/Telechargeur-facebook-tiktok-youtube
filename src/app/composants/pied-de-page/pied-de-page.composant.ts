import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-pied-de-page',
  standalone: true,
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <footer class="bg-surface-container-low dark:bg-inverse-surface text-primary dark:text-inverse-primary text-sm w-full py-12 border-t border-outline-variant dark:border-outline mt-auto transition-colors duration-300">
      <div class="flex flex-col md:flex-row justify-between items-center px-6 md:px-10 gap-6 max-w-7xl mx-auto">
        <a routerLink="/" class="text-xl font-black text-primary dark:text-inverse-primary tracking-tight">
          MediaFetch
        </a>

        <nav class="flex flex-wrap justify-center gap-6 text-on-surface-variant dark:text-outline-variant font-medium">
          <a routerLink="/comment-utiliser" class="hover:underline decoration-primary transition-all">Comment utiliser</a>
          <a routerLink="/sites-supportes" class="hover:underline decoration-primary transition-all">Sites supportés</a>
          <a routerLink="/faq" class="hover:underline decoration-primary transition-all">FAQ</a>
          <a routerLink="/historique" class="hover:underline decoration-primary transition-all">Historique</a>
        </nav>

        <div class="text-on-surface-variant dark:text-outline-variant text-center md:text-right text-xs">
          © 2026 MediaFetch - Velocity Stream. Tous droits réservés.
        </div>
      </div>
    </footer>
  `
})
export class ComposantPiedDePage {}
