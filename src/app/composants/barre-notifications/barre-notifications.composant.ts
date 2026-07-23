import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { ServiceNotification } from '../../services/notification.service';

@Component({
  selector: 'app-barre-notifications',
  standalone: true,
  imports: [MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-md w-full px-4 pointer-events-none">
      @for (notification of serviceNotification.listeNotifications(); track notification.identifiant) {
        <div class="pointer-events-auto flex items-center justify-between gap-3 p-4 rounded-xl shadow-lg border text-sm font-medium transition-all duration-300 animation-apparition"
             [class.bg-emerald-50]="notification.type === 'succes'"
             [class.text-emerald-900]="notification.type === 'succes'"
             [class.border-emerald-200]="notification.type === 'succes'"
             [class.bg-rose-50]="notification.type === 'erreur'"
             [class.text-rose-900]="notification.type === 'erreur'"
             [class.border-rose-200]="notification.type === 'erreur'"
             [class.bg-primary-container]="notification.type === 'info'"
             [class.text-on-primary-container]="notification.type === 'info'"
             [class.border-primary]="notification.type === 'info'">
          <div class="flex items-center gap-2.5">
            <mat-icon class="text-xl">
              @if (notification.type === 'succes') { check_circle }
              @else if (notification.type === 'erreur') { error }
              @else { info }
            </mat-icon>
            <span>{{ notification.texte }}</span>
          </div>

          <button (click)="serviceNotification.supprimerNotification(notification.identifiant)"
                  type="button"
                  class="p-1 rounded-lg opacity-70 hover:opacity-100 transition-opacity">
            <mat-icon class="text-lg">close</mat-icon>
          </button>
        </div>
      }
    </div>
  `
})
export class ComposantBarreNotifications {
  public serviceNotification = inject(ServiceNotification);
}
