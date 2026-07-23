import { ChangeDetectionStrategy, Component, EventEmitter, Output, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatIconModule } from '@angular/material/icon';
import { ServiceNotification } from '../../services/notification.service';

@Component({
  selector: 'app-modal-connexion',
  standalone: true,
  imports: [FormsModule, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animation-apparition">
      <div class="bg-surface dark:bg-inverse-surface border border-outline-variant/40 dark:border-outline rounded-2xl p-6 md:p-8 max-w-md w-full shadow-2xl text-on-surface dark:text-inverse-on-surface relative">
        
        <button (click)="evenementFermer.emit()"
                type="button"
                class="absolute top-4 right-4 p-2 rounded-full text-on-surface-variant hover:bg-surface-container-high transition-colors">
          <mat-icon>close</mat-icon>
        </button>

        <div class="text-center mb-6">
          <div class="w-12 h-12 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-3">
            <mat-icon class="text-2xl">lock</mat-icon>
          </div>
          <h2 class="text-2xl font-bold text-on-surface dark:text-inverse-on-surface">Espace Membre</h2>
          <p class="text-sm text-on-surface-variant dark:text-outline-variant mt-1">Connectez-vous pour enregistrer vos téléchargements illimités.</p>
        </div>

        <form (ngSubmit)="soumettreFormulaire()" class="flex flex-col gap-4">
          <div>
            <label for="champCourriel" class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant dark:text-outline-variant mb-1.5">
              Adresse e-mail
            </label>
            <div class="relative">
              <mat-icon class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-xl">email</mat-icon>
              <input id="champCourriel"
                     type="email"
                     [(ngModel)]="adresseCourriel"
                     name="adresseCourriel"
                     placeholder="votre.email@domaine.com"
                     required
                     class="w-full pl-10 pr-4 py-3 bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant dark:border-outline rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
          </div>

          <div>
            <label for="champMotDePasse" class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant dark:text-outline-variant mb-1.5">
              Mot de passe
            </label>
            <div class="relative">
              <mat-icon class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-xl">key</mat-icon>
              <input id="champMotDePasse"
                     type="password"
                     [(ngModel)]="motDePasse"
                     name="motDePasse"
                     placeholder="••••••••"
                     required
                     class="w-full pl-10 pr-4 py-3 bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant dark:border-outline rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
          </div>

          <button type="submit"
                  [disabled]="estChargement()"
                  class="mt-2 bg-primary hover:bg-primary-container text-on-primary font-semibold py-3 px-6 rounded-xl shadow-md transition-all active:scale-95 flex items-center justify-center gap-2">
            @if (estChargement()) {
              <mat-icon class="animate-spin text-xl">sync</mat-icon>
              <span>Connexion en cours...</span>
            } @else {
              <mat-icon class="text-xl">login</mat-icon>
              <span>Se connecter</span>
            }
          </button>
        </form>
      </div>
    </div>
  `
})
export class ComposantModalConnexion {
  @Output() public evenementFermer = new EventEmitter<void>();

  public adresseCourriel = '';
  public motDePasse = '';
  public estChargement = signal<boolean>(false);

  private serviceNotification = inject(ServiceNotification);

  public soumettreFormulaire(): void {
    if (!this.adresseCourriel || !this.motDePasse) {
      this.serviceNotification.afficherErreur('Veuillez remplir tous les champs.');
      return;
    }

    this.estChargement.set(true);
    setTimeout(() => {
      this.estChargement.set(false);
      this.serviceNotification.afficherSucces(`Bienvenue, ${this.adresseCourriel} !`);
      this.evenementFermer.emit();
    }, 1200);
  }
}
