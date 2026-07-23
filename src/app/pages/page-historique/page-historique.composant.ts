import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { ServiceStockageMedia, ElementHistoriqueMedia } from '../../services/stockage-media.service';
import { ServiceTelechargement } from '../../services/telechargement.service';

@Component({
  selector: 'app-page-historique',
  standalone: true,
  imports: [RouterLink, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <main class="flex-grow flex flex-col items-center py-12 md:py-20 px-4 md:px-10 max-w-5xl mx-auto w-full animation-apparition">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between w-full mb-8 gap-4">
        <div>
          <h1 class="text-3xl font-black text-on-background dark:text-inverse-on-surface tracking-tight">
            Historique des Téléchargements
          </h1>
          <p class="text-sm text-on-surface-variant dark:text-outline-variant mt-1">
            Retrouvez tous vos médias précédemment extraits et enregistrés.
          </p>
        </div>

        @if (serviceStockage.historique().length > 0) {
          <button (click)="serviceStockage.viderHistorique()"
                  type="button"
                  class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-error-container text-on-error-container hover:bg-error hover:text-on-error transition-colors">
            <mat-icon class="text-sm">delete_sweep</mat-icon>
            <span>Effacer l'historique</span>
          </button>
        }
      </div>

      @if (serviceStockage.historique().length > 0) {
        <div class="flex flex-col gap-4 w-full">
          @for (item of serviceStockage.historique(); track item.identifiant) {
            <div class="bg-surface-container-lowest dark:bg-inverse-surface p-4 sm:p-5 rounded-2xl border border-outline-variant/40 dark:border-outline shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:border-primary/50 transition-colors">
              <div class="flex items-center gap-4">
                <img [src]="item.media.miniatureUrl"
                     [alt]="item.media.titre"
                     referrerpolicy="no-referrer"
                     class="w-16 h-12 object-cover rounded-lg bg-surface-container-high" />

                <div>
                  <h3 class="font-bold text-sm text-on-surface dark:text-inverse-on-surface line-clamp-1">
                    {{ item.media.titre }}
                  </h3>
                  <p class="text-xs text-on-surface-variant dark:text-outline-variant mt-0.5">
                    {{ item.formatUtilise.nomFormat }} • {{ item.tailleFichierTexte }} • {{ item.dateTelechargement }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 self-end sm:self-auto">
                <button (click)="retelecharger(item)"
                        type="button"
                        title="Télécharger à nouveau"
                        class="p-2.5 rounded-xl bg-primary/10 text-primary dark:text-inverse-primary hover:bg-primary hover:text-on-primary transition-colors flex items-center justify-center">
                  <mat-icon class="text-lg">download</mat-icon>
                </button>

                <button (click)="serviceStockage.supprimerElement(item.identifiant)"
                        type="button"
                        title="Supprimer de l'historique"
                        class="p-2.5 rounded-xl text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 transition-colors flex items-center justify-center">
                  <mat-icon class="text-lg">delete</mat-icon>
                </button>
              </div>
            </div>
          }
        </div>
      } @else {
        <div class="text-center py-20 bg-surface-container-lowest dark:bg-inverse-surface border border-outline-variant/40 rounded-2xl w-full p-8">
          <div class="w-16 h-16 bg-surface-container dark:bg-surface-container-high rounded-full flex items-center justify-center mx-auto mb-4 text-on-surface-variant">
            <mat-icon class="text-3xl">history</mat-icon>
          </div>
          <h3 class="text-lg font-bold text-on-surface dark:text-inverse-on-surface mb-2">
            Votre historique est vide
          </h3>
          <p class="text-sm text-on-surface-variant dark:text-outline-variant mb-6">
            Vous n'avez pas encore téléchargé de vidéo dans cette session.
          </p>
          <a routerLink="/" class="inline-flex items-center gap-2 bg-primary text-on-primary px-6 py-3 rounded-full font-semibold text-sm">
            <mat-icon class="text-sm">add</mat-icon>
            <span>Télécharger une vidéo</span>
          </a>
        </div>
      }
    </main>
  `
})
export class ComposantPageHistorique {
  public serviceStockage = inject(ServiceStockageMedia);
  private serviceTelechargement = inject(ServiceTelechargement);

  public retelecharger(item: ElementHistoriqueMedia): void {
    this.serviceTelechargement.lancerTelechargement(item.media, item.formatUtilise);
  }
}
