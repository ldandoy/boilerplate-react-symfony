import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/react';
import { toast } from 'react-toastify';
import { ToastProvider } from './ToastContext';
import { useToast } from '../hooks/useToast';

vi.mock('react-toastify', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-toastify')>();
  return {
    ...actual,
    toast: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
  };
});

function ToastButtons() {
  const notify = useToast();
  return (
    <>
      <button onClick={() => notify.success('ok', { autoClose: 1000 })}>success</button>
      <button onClick={() => notify.error('ko')}>error</button>
      <button onClick={() => notify.info('fyi')}>info</button>
      <button onClick={() => notify.warning('careful')}>warning</button>
    </>
  );
}

describe('ToastProvider', () => {
  it('forwards each notification type to react-toastify', () => {
    render(
      <ToastProvider>
        <ToastButtons />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('success'));
    fireEvent.click(screen.getByText('error'));
    fireEvent.click(screen.getByText('info'));
    fireEvent.click(screen.getByText('warning'));

    expect(toast.success).toHaveBeenCalledWith('ok', { autoClose: 1000 });
    expect(toast.error).toHaveBeenCalledWith('ko', undefined);
    expect(toast.info).toHaveBeenCalledWith('fyi', undefined);
    expect(toast.warning).toHaveBeenCalledWith('careful', undefined);
  });
});
