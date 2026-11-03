import { CommonService } from './common.service';

describe('Shared loading overlay', () => {
  let service: CommonService;
  let overlay: any;
  let controller: any;

  beforeEach(() => {
    overlay = { present: jasmine.createSpy().and.resolveTo(), dismiss: jasmine.createSpy().and.resolveTo(true) };
    controller = { create: jasmine.createSpy().and.resolveTo(overlay) };
    service = new CommonService(controller, {} as any, {} as any, {} as any, {} as any, {} as any);
  });

  it('keeps one overlay until all concurrent requests finish', async () => {
    await Promise.all([service.showLoader(), service.showLoader()]);
    expect(controller.create).toHaveBeenCalledTimes(1);
    await service.dismissLoading();
    expect(overlay.dismiss).not.toHaveBeenCalled();
    await service.dismissLoading();
    expect(overlay.dismiss).toHaveBeenCalledTimes(1);
    expect(service.loading).toBeNull();
  });

  it('does not leave a blocker when the response arrives before creation finishes', async () => {
    let resolveCreate!: (value: any) => void;
    controller.create.and.returnValue(new Promise(resolve => resolveCreate = resolve));
    const showing = service.showLoader();
    await Promise.resolve();
    const dismissing = service.dismissLoading();
    resolveCreate(overlay);
    await Promise.all([showing, dismissing]);
    expect(overlay.dismiss).toHaveBeenCalledTimes(1);
    expect(service.loading).toBeNull();
  });

  it('does not create an overlay when a request finishes immediately', async () => {
    await Promise.all([service.showLoader(), service.dismissLoading()]);
    expect(controller.create).not.toHaveBeenCalled();
  });
});
