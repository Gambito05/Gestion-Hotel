import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FrmReserva } from './frm-reserva';

describe('FrmReserva', () => {
  let component: FrmReserva;
  let fixture: ComponentFixture<FrmReserva>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FrmReserva],
    }).compileComponents();

    fixture = TestBed.createComponent(FrmReserva);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
