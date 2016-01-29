<?php

namespace KomerciTest;

use Komerci\CaptureCancel;

/**
 * CaptureCancelTest
 *
 * @author eltondev
 */
class CaptureCancelTest extends AbstractTest
{
    public function testCaptureCancel()
    {
        $cancel = new CaptureCancel();
        $cancel->setFiliacao('037916785');
        $cancel->setTotal(10);
        $cancel->setData('20130116');
        $cancel->setNumAutor('123456');
        $cancel->setNumCv('123456789');
        $cancel->setUsr('chongas');
        $cancel->setPwd('mariola');
        $result = $cancel->send();

        $this->assertInstanceOf('\Komerci\CaptureCancelResponse', $result);
    }

}
