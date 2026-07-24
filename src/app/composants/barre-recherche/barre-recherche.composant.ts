import { ChangeDetectionStrategy, Component, EventEmitter, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-barre-recherche',
  standalone: true,
  imports: [FormsModule, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="w-full max-w-3xl mx-auto flex flex-col items-center px-1 sm:px-0">
      <div class="w-full bg-surface-container-lowest dark:bg-surface-container-high p-1.5 sm:p-2 md:p-3 rounded-2xl shadow-xl border border-outline-variant/60 dark:border-outline flex items-center gap-1.5 sm:gap-2 transition-all focus-within:ring-2 focus-within:ring-primary">
        <mat-icon class="text-on-surface-variant ml-2 sm:ml-3 text-xl sm:text-2xl hidden sm:block">link</mat-icon>

        <input type="url"
               [(ngModel)]="adresseSaisie"
               (keyup.enter)="lancerAnalyse()"
               placeholder="Collez l'URL de la vidéo ici..."
               class="flex-grow px-2 sm:px-3 py-2 bg-transparent text-on-surface dark:text-inverse-on-surface text-sm sm:text-base md:text-lg focus:outline-none font-medium placeholder:text-on-surface-variant/70 min-w-0" />

        @if (adresseSaisie) {
          <button (click)="effacerSaisie()"
                  type="button"
                  title="Effacer le champ"
                  class="p-2 text-on-surface-variant hover:text-on-surface transition-colors">
            <mat-icon class="text-xl sm:text-2xl">clear</mat-icon>
          </button>
        } @else {
          <button (click)="collerPressePapier()"
                  type="button"
                  title="Coller le lien"
                  class="flex items-center gap-1 text-xs font-semibold px-2.5 sm:px-3 py-1.5 rounded-lg bg-surface-container dark:bg-surface-container-highest text-on-surface-variant hover:text-primary transition-colors shrink-0">
            <mat-icon class="text-base">content_paste</mat-icon>
            <span class="hidden xs:inline">Coller</span>
          </button>
        }

        <button (click)="lancerAnalyse()"
                [disabled]="!adresseSaisie.trim()"
                type="button"
                class="bg-primary hover:bg-primary-container text-on-primary font-semibold px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl shadow-md transition-all active:scale-95 flex items-center gap-1.5 sm:gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary disabled:active:scale-100 shrink-0">
          <mat-icon class="text-lg sm:text-xl">download</mat-icon>
          <span class="hidden sm:inline">Télécharger</span>
        </button>
      </div>

      <!-- Quick Platform Chips -->
      <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 mt-4 sm:mt-6 text-xs sm:text-sm font-medium">
        <button (click)="remplirExemple('https://youtube.com/watch?v=demo_data_structures')"
                type="button"
                class="group flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-surface-container-low dark:bg-surface-container-high hover:bg-[#FF0000] transition-all duration-200 shadow-sm border border-outline-variant/40">
          <mat-icon class="text-base sm:text-lg text-black">play_circle</mat-icon>
          <span class="text-black font-medium">YouTube</span>
        </button>

        <button (click)="remplirExemple('https://tiktok.com/@utilisateur/video/7891234')"
                type="button"
                class="group flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-surface-container-low dark:bg-surface-container-high hover:bg-[#000000] transition-all duration-200 shadow-sm border border-outline-variant/40">
          <mat-icon class="text-base sm:text-lg text-black group-hover:text-white transition-colors duration-200">
            music_note
          </mat-icon>
          <span class="text-black group-hover:text-white font-medium transition-colors duration-200">
            TikTok
          </span>
        </button>

        <button (click)="remplirExemple('https://facebook.com/watch/?v=123456789')"
                type="button"
                class="group flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-surface-container-low dark:bg-surface-container-high hover:bg-[#1877F2] transition-all duration-200 shadow-sm border border-outline-variant/40">
          <mat-icon class="text-base sm:text-lg text-black">facebook</mat-icon>
          <span class="text-black font-medium">Facebook</span>
        </button>
      </div>
    </div>
  `
})
export class ComposantBarreRecherche {
  public adresseSaisie = '';

  @Output() public evenementSoumissionUrl = new EventEmitter<string>();

  public lancerAnalyse(): void {
    if (this.adresseSaisie.trim()) {
      this.evenementSoumissionUrl.emit(this.adresseSaisie.trim());
    }
  }

  public effacerSaisie(): void {
    this.adresseSaisie = '';
  }

  public remplirExemple(url: string): void {
    this.adresseSaisie = url;
    this.lancerAnalyse();
  }

  public async collerPressePapier(): Promise<void> {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
      try {
        const texte = await navigator.clipboard.readText();
        if (texte) {
          this.adresseSaisie = texte;
        }
      } catch {
        // Ignorer si bloqué par les permissions navigateur
      }
    }
  }
}
