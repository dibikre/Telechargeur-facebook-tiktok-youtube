import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { OptionFormatMedia } from '../../modeles/format.modele';

@Component({
  selector: 'app-carte-format',
  standalone: true,
  imports: [MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 sm:p-4 rounded-xl border border-outline-variant/60 dark:border-outline bg-white dark:bg-surface-container-high hover:border-primary transition-all duration-200 group gap-3.5 sm:gap-4">
      <div class="flex items-center gap-3 sm:gap-4">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-primary-fixed dark:bg-primary/20 flex items-center justify-center text-primary dark:text-inverse-primary group-hover:bg-primary group-hover:text-on-primary transition-colors duration-200 shrink-0">
          <mat-icon class="text-xl sm:text-2xl">{{ formatMedia.iconeNom }}</mat-icon>
        </div>

        <div class="flex flex-col min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-bold text-on-surface dark:text-inverse-on-surface text-sm sm:text-base truncate">
              {{ formatMedia.nomFormat }}
            </span>
            @if (formatMedia.estHauteDefinition) {
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-primary-container text-on-primary-container shrink-0">
                HD
              </span>
            }
          </div>
          <span class="text-xs text-on-surface-variant dark:text-outline-variant font-medium mt-0.5 truncate">
            {{ formatMedia.qualiteLabel }} • {{ formatMedia.tailleTexte }}
          </span>
        </div>
      </div>

      <button (click)="evenementTelecharger.emit(formatMedia)"
              type="button"
              class="w-full sm:w-auto bg-surface-container dark:bg-surface-container-high hover:bg-primary hover:text-on-primary text-primary dark:text-inverse-primary font-semibold px-5 sm:px-6 py-2.5 rounded-full transition-all duration-200 flex items-center justify-center gap-2 text-sm shadow-sm active:scale-95 shrink-0">
        <mat-icon class="text-base sm:text-sm">download</mat-icon>
        <span>Télécharger</span>
      </button>
    </div>
  `
})
export class ComposantCarteFormat {
  @Input({ required: true }) public formatMedia!: OptionFormatMedia;
  @Output() public evenementTelecharger = new EventEmitter<OptionFormatMedia>();
}
