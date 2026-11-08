import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';

@Component({
  imports: [RouterModule, MatIconModule],
  selector: 'app-error404-component',
  styleUrl: './error404-component.css',
  templateUrl: './error404-component.html',
})
export class Error404Component {}
