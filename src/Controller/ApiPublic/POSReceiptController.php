<?php

namespace App\Controller\ApiPublic;

use App\Entity\POS\Employee;
use App\Entity\POS\POSOrder;
use App\Entity\POS\POSReceipt;
use App\Exception\AppException;
use App\Repository\POS\EmployeeRepository;
use App\Repository\POS\POSOrderRepository;
use App\Repository\POS\POSReceiptRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class POSReceiptController extends FOSRestController
{
//    /**
//     * @Rest\Post("/pos/receipt/{workspaceName}/{employee}/{username}/{pin}")
//     *
//     * @SWG\Parameter( name="workspaceName",    in="path", type="string", description="Workspace name" )
//     * @SWG\Parameter( name="employee",         in="path", type="integer", description="Employee ID" )
//     * @SWG\Parameter( name="username",         in="path", type="string", description="Employee printer username" )
//     * @SWG\Parameter( name="pin",              in="path", type="integer", description="Employee printer pin" )
//     * @SWG\Response(response=201, description="Array with new POSReceipt for printer")
//     * @SWG\Tag(name="POS Receipt")
//     *
//     * @param string $workspaceName
//     * @param int $employee
//     * @param string $username
//     * @param int $pin
//     * @param Request $request
//     * @param EmployeeRepository $employeeRepository
//     * @param POSReceiptRepository $POSReceiptRepository
//     * @return View
//     * @throws AppException
//     * @throws \Doctrine\ORM\NonUniqueResultException
//     */
//    public function prepareReceipt(string $workspaceName, int $employee, string $username, int $pin, Request $request, EmployeeRepository $employeeRepository, POSReceiptRepository $POSReceiptRepository) : View
//    {
//        return $this->view([], JsonResponse::HTTP_OK);
//
//        /** @var Employee $employee */
//        $employee = $employeeRepository->findOneBy(['id' => $employee, 'printerUsername' => $username, 'printerPin' => $pin]);
//        if(!($employee instanceof Employee)) throw new AppException('Employee not found');
//        if($employee->getWorkspace()->getName() !== $workspaceName) throw new AppException('Employee not found');
//
//        /** @var POSReceipt $POSReceipt */
//        $POSReceipt = $POSReceiptRepository->findPrintingByEmployee($employee);
//        if($POSReceipt instanceof POSReceipt) {
//            $result = ['jobReady' => false];
//            return $this->view($result, JsonResponse::HTTP_OK);
//        }
//
//        // find recent receipt for the employee
//        /** @var POSReceipt $POSReceipt */
//        $POSReceipt = $POSReceiptRepository->findForPrintByEmployee($employee);
//        if($POSReceipt instanceof POSReceipt){
//            $result = [
//                "jobReady" => true,
//                "mediaTypes" => ["image/png", "text/plain"],
//                "deleteMethod" => "DELETE",
//                "clientAction" => [["request" => "SetID", "options" => $POSReceipt->getId()]]
//            ];
//
//            try{
//                // change status to PRINTING
//                $POSReceipt->setStatus(POSReceipt::STATUS_PRINTING);
//                $POSReceiptRepository->save($POSReceipt);
//            }catch (\Exception $exception){
//                // TODO any action required?
//            }
//        }else{
//            $result = ['jobReady' => false];
//        }
//
//        return $this->view($result, JsonResponse::HTTP_OK);
//    }
//
//    /**
//     * @Rest\Get("/pos/receipt/{workspaceName}/{employee}/{username}/{pin}")
//     *
//     * @SWG\Parameter( name="workspaceName",    in="path", type="string", description="Workspace name" )
//     * @SWG\Parameter( name="employee",         in="path", type="integer", description="Employee ID" )
//     * @SWG\Parameter( name="username",         in="path", type="string", description="Employee printer username" )
//     * @SWG\Parameter( name="pin",              in="path", type="integer", description="Employee printer pin" )
//     *
//     * @SWG\Parameter( name="mac",              in="query", type="string", description="MAC address of the printer", required=true)
//     * @SWG\Parameter( name="uid",              in="query", type="string", description="Unique ID of the receipt to print", required=true )
//     *
//     * @SWG\Response(response=200, description="Array with new POSReceipt for printer")
//     * @SWG\Tag(name="POS Receipt")
//     *
//     * @param string $workspaceName
//     * @param int $employee
//     * @param string $username
//     * @param int $pin
//     * @param Request $request
//     * @param EmployeeRepository $employeeRepository
//     * @param POSReceiptRepository $POSReceiptRepository
//     * @return View
//     * @throws AppException
//     * @throws \Doctrine\ORM\NonUniqueResultException
//     */
//    public function printReceipt(string $workspaceName, int $employee, string $username, int $pin, Request $request, EmployeeRepository $employeeRepository, POSReceiptRepository $POSReceiptRepository) : View
//    {
//        return $this->view([], JsonResponse::HTTP_OK);
//
//        /** @var Employee $employee */
//        $employee = $employeeRepository->findOneBy(['id' => $employee, 'printerUsername' => $username, 'printerPin' => $pin]);
//        if(!($employee instanceof Employee)) throw new AppException('Employee not found');
//        if($employee->getWorkspace()->getName() !== $workspaceName) throw new AppException('Employee not found');
//
//        $printerMac = $request->query->get('mac');
//        if(strtoupper($employee->getPrinterMac()) !== strtoupper($printerMac)) throw new AppException('Employee printer not found');
//
//        /** @var POSReceipt $POSReceipt */
//        $POSReceipt = $POSReceiptRepository->findPrintingByEmployee($employee);
//        if(!($POSReceipt instanceof POSReceipt)) throw new AppException('Receipt is currently printing');
//
//        $receiptId = $request->query->get('uid');
//        if(strtolower($receiptId) !== strtolower(substr($POSReceipt->getId(), 0 , 31))){
//            throw new AppException('Receipt not found');
//        }
//
//        header('X-Star-ImageDitherPattern: none');
//        header('X-Star-Buzzerstartpattern: 3');
//        header('X-Star-Cut: full; feed=true');
//
//
//        $receipt = new \App\Service\Printer\ReceiptGenerator(
//            $POSReceipt->getPOSOrder()->getCurrencyPair()->getBaseCurrency()->getFullName() . ' ' . $POSReceipt->getPOSOrder()->getCurrencyPair()->getBaseCurrency()->getShortName(),
//            $POSReceipt->getPOSOrder()->toPrecision($POSReceipt->getPOSOrder()->getAmount()) . ' ' . $POSReceipt->getPOSOrder()->getCurrencyPair()->getBaseCurrency()->getShortName(),
//            $POSReceipt->getPOSOrder()->getPhone(),
//            $POSReceipt->getPOSOrder()->toPrecisionQuoted($POSReceipt->getPOSOrder()->getTotalPrice()) . ' ' . $POSReceipt->getPOSOrder()->getCurrencyPair()->getQuotedCurrency()->getShortName(),
////            'K9al13Vablq/dkpw34sb915'
//            $POSReceipt->getPOSOrder()->getRedeemHash().'/'.substr(md5($POSReceipt->getPOSOrder()->getRedeemHash()), 7, 14)
////            substr(md5(uniqid()), 0, 7).'/'.substr(md5(uniqid()), 0, 8)
//        );
//
////        $receipt = new \App\Service\Printer\ReceiptGenerator(
////            $POSReceipt->getPOSOrder()->getCurrencyPair()->getBaseCurrency()->getFullName(),
////            $POSReceipt->getPOSOrder()->getAmount() . ' ' . $POSReceipt->getPOSOrder()->getCurrencyPair()->getBaseCurrency()->getShortName(),
////            $POSReceipt->getPOSOrder()->getPhone() . ' ' . ,
////            $POSReceipt->getPOSOrder()->getTotalPrice(),
////            $POSReceipt->getPOSOrder()->getSignature().'/'.$POSReceipt->getPOSOrder()->getRedeemHash()
////        );
//
//        $receipt->loadBackground('pos/receipt_kolo.png');
//        $receipt->loadFont('pos/Montserrat-Medium.ttf');
//        $receipt->generateRecipt();
//
//        exit;
//    }
//
//    /**
//     * @Rest\Delete("/pos/receipt/{workspaceName}/{employee}/{username}/{pin}")
//     *
//     * @SWG\Parameter( name="workspaceName",    in="path", type="string", description="Workspace name" )
//     * @SWG\Parameter( name="employee",         in="path", type="integer", description="Employee ID" )
//     * @SWG\Parameter( name="username",         in="path", type="string", description="Employee printer username" )
//     * @SWG\Parameter( name="pin",              in="path", type="integer", description="Employee printer pin" )
//     *
//     * @SWG\Parameter( name="mac",              in="query", type="string", description="MAC address of the printer", required=true)
//     * @SWG\Parameter( name="uid",              in="query", type="string", description="Unique ID of the receipt to print", required=true )
//     *
//     * @SWG\Response(response=200, description="Array with new POSReceipt for printer")
//     * @SWG\Tag(name="POS Receipt")
//     *
//     * @param string $workspaceName
//     * @param int $employee
//     * @param string $username
//     * @param int $pin
//     * @param Request $request
//     * @param EmployeeRepository $employeeRepository
//     * @param POSReceiptRepository $POSReceiptRepository
//     * @param POSOrderRepository $POSOrderRepository
//     * @return View
//     * @throws AppException
//     * @throws \Doctrine\ORM\NonUniqueResultException
//     * @throws \Doctrine\ORM\ORMException
//     * @throws \Doctrine\ORM\OptimisticLockException
//     * @throws \Exception
//     */
//    public function deleteReceipt(string $workspaceName, int $employee, string $username, int $pin, Request $request, EmployeeRepository $employeeRepository, POSReceiptRepository $POSReceiptRepository, POSOrderRepository $POSOrderRepository) : View
//    {
//        return $this->view([], JsonResponse::HTTP_OK);
//
//        /** @var Employee $employee */
//        $employee = $employeeRepository->findOneBy(['id' => $employee, 'printerUsername' => $username, 'printerPin' => $pin]);
//        if(!($employee instanceof Employee)) throw new AppException('Employee not found');
//        if($employee->getWorkspace()->getName() !== $workspaceName) throw new AppException('Employee not found');
//
//        $printerMac = $request->query->get('mac');
//        if(strtoupper($employee->getPrinterMac()) !== strtoupper($printerMac)) throw new AppException('Employee printer not found');
//
//        /** @var POSReceipt $POSReceipt */
//        $POSReceipt = $POSReceiptRepository->findPrintingByEmployee($employee);
//        if(!($POSReceipt instanceof POSReceipt)) throw new AppException('Receipt not found');
//
//        $receiptId = $request->query->get('uid');
//        if(strtolower($receiptId) !== strtolower(substr($POSReceipt->getId(), 0 , 31))){
//            throw new AppException('Receipt not found');
//        }
//
//        // change status to PRINTING
//        $POSReceipt->setStatus(POSReceipt::STATUS_PRINTED);
//        $POSReceipt->setPrintedAt(new \DateTime('now'));
//        $POSReceiptRepository->save($POSReceipt);
//
//        $POSReceipt->getPOSOrder()->setStatus(POSOrder::STATUS_FILLED);
//        $POSOrderRepository->save($POSReceipt->getPOSOrder());
//
//        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
//    }
}
