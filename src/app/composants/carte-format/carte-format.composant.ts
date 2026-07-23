import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { OptionFormatMedia } from '../../modeles/format.modele';

@Component({
  selector: 'app-carte-format',
  standalone: true,
  imports: [MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-outline-variant/60 dark:border-outline bg-white dark:bg-surface-container-high hover:border-primary transition-all duration-200 group gap-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-primary-fixed dark:bg-primary/20 flex items-center justify-center text-primary dark:text-inverse-primary group-hover:bg-primary group-hover:text-on-primary transition-colors duration-200">
          <mat-icon>{{ formatMedia.iconeNom }}</mat-icon>
        </div>

        <div class="flex flex-col">
          <div class="flex items-center gap-2">
            <span class="font-bold text-on-surface dark:text-inverse-on-surface text-base">
              {{ formatMedia.nomFormat }}
            </span>
            @if (formatMedia.estHauteDefinition) {
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-primary-container text-on-primary-container">
                HD
              </span>
            }
          </div>
          <span class="text-xs text-on-surface-variant dark:text-outline-variant font-medium mt-0.5">
            {{ formatMedia.qualiteLabel }} • {{ formatMedia.tailleTexte }}
          </span>
        </div>
      </div>

      <button (click)="evenementTelecharger.emit(formatMedia)"
              type="button"
              class="bg-surface-container dark:bg-surface-container-high hover:bg-primary hover:text-on-primary text-primary dark:text-inverse-primary font-semibold px-6 py-2.5 rounded-full transition-all duration-200 flex items-center justify-center gap-2 text-sm shadow-sm active:scale-95">
        <mat-icon class="text-sm">download</mat-icon>
        <span>Télécharger</span>
      </button>
    </div>
  `
})
export class ComposantCarteFormat {
  @Input({ required: true }) public formatMedia!: OptionFormatMedia;
  @Output() public evenementTelecharger = new EventEmitter<OptionFormatMedia>();
}
