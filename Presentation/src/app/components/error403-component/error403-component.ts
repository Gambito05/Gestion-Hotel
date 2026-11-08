import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';

@Component({
  imports: [RouterModule, MatIconModule],
  selector: 'app-error403-component',
  styleUrl: './error403-component.css',
  templateUrl: './error403-component.html',
})
export class Error403Component {}
