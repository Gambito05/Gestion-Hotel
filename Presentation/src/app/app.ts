import { Component, HostListener, inject, signal } from '@angular/core';
import { RouterModule, RouterOutlet } from '@angular/router';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatMenuModule } from '@angular/material/menu';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatDividerModule } from '@angular/material/divider';
import { AuthService } from './shared/services/auth-service';

type MenuItem = {
  label : string;
  icon : string;
  route : string;
  rol : number[];
}

@Component({
  imports: [RouterOutlet, RouterModule, MatToolbarModule, MatButtonModule, MatIconModule,
    MatTooltipModule, MatMenuModule, MatSidenavModule, MatListModule, MatDividerModule
  ],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {

  protected readonly title = signal('gestion_hotel');
  protected readonly authService = inject(AuthService);


menuItems: MenuItem[] = [
    {label: 'Inicio', icon: 'home', route:'home', rol :[1, 2, 3]},
    {label: 'Usuarios', icon: 'group', route:'usuario', rol : [1, 2]},
    {label: 'Habitaciones', icon: 'bed', route:'habitacion', rol :[1, 2, 3]},
    {label: 'Tours', icon: 'tour', route:'tour', rol :[1, 2, 3]},
    {label: 'Reservas', icon: 'calendar_month', route: 'reserva', rol: [1, 2, 3]} ];

    @HostListener('window:beforeunload') unloadHandler() {
    this.loggout();
    }

    @HostListener('window:popstate')
    onBrowserBack(): void {
      this.loggout();
    }

    loggout(){
      this.authService.loggout();
    }

}
