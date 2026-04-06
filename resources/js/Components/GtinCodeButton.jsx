import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import JsBarcode from 'jsbarcode';
import QRCode from 'qrcode';
import { useEffect, useRef, useState } from 'react';

export default function GtinCodeButton({
    value,
    children,
    className = 'text-blue-600 hover:underline',
    modalMaxWidth = 'md',
}) {
    const [showModal, setShowModal] = useState(false);
    const qrCanvasRef = useRef(null);
    const barcodeCanvasRef = useRef(null);
    const gtinValue = value ? String(value) : '';

    const openModal = () => {
        if (!gtinValue) return;
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
    };

    const downloadCanvas = (canvas, filename) => {
        if (!canvas) return;
        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = filename;
        link.click();
    };

    useEffect(() => {
        if (!showModal || !gtinValue) return;

        let timeoutId;
        const renderCodes = () => {
            if (!qrCanvasRef.current || !barcodeCanvasRef.current) {
                timeoutId = setTimeout(renderCodes, 50);
                return;
            }

            QRCode.toCanvas(qrCanvasRef.current, gtinValue, {
                width: 220,
                margin: 1,
                errorCorrectionLevel: 'M',
            });

            JsBarcode(barcodeCanvasRef.current, gtinValue, {
                format: 'CODE128',
                width: 2,
                height: 80,
                margin: 0,
                displayValue: true,
                fontSize: 16,
            });
        };

        renderCodes();

        return () => {
            if (timeoutId) clearTimeout(timeoutId);
        };
    }, [showModal, gtinValue]);

    if (!gtinValue) {
        return children || value || null;
    }

    return (
        <>
            <button type="button" onClick={openModal} className={className}>
                {children || gtinValue}
            </button>

            <Modal show={showModal} onClose={closeModal} maxWidth={modalMaxWidth}>
                <div className="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div className="bg-slate-100 border-b border-slate-200 px-6 py-4">
                        <h2 className="text-lg font-semibold text-slate-800">
                            {gtinValue}
                        </h2>
                    </div>
                    <div className="p-6 space-y-6">
                        <div className="flex flex-col items-center gap-3">
                            <div className="text-sm text-slate-600">QR Code</div>
                            <canvas ref={qrCanvasRef} className="bg-white border border-slate-200 rounded p-2" />
                            <SecondaryButton onClick={() => downloadCanvas(qrCanvasRef.current, `gtin-${gtinValue}-qr.png`)}>
                                Download QR
                            </SecondaryButton>
                        </div>
                        <div className="flex flex-col items-center gap-3">
                            <div className="text-sm text-slate-600">Bar Code</div>
                            <canvas ref={barcodeCanvasRef} className="bg-white border border-slate-200 rounded p-2" />
                            <SecondaryButton onClick={() => downloadCanvas(barcodeCanvasRef.current, `gtin-${gtinValue}-barcode.png`)}>
                                Download Bar Code
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </Modal>
        </>
    );
}
