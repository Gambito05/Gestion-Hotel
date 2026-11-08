import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FrmReservaTour } from './frm-reserva-tour';

describe('FrmReservaTour', () => {
  let component: FrmReservaTour;
  let fixture: ComponentFixture<FrmReservaTour>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FrmReservaTour],
    }).compileComponents();

    fixture = TestBed.createComponent(FrmReservaTour);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
