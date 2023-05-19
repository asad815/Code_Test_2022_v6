<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            return response($this->repository->getUsersJobs($user_id));

        }
        elseif($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            return response($this->repository->getAll($request));
        }

    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return response($this->repository->with('translatorJobRel.user')->find($id));

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
       return response($this->repository->store($request->__authenticatedUser, $request->all()));

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        return response($this->repository->updateJob($id, array_except($request->all(), ['_token', 'submit']),  $request->__authenticatedUser));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        return  response($this->repository->storeJobEmail($request->all()));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {
            return response$this->repository->getUsersJobsHistory($user_id, $request));
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        return response($this->repository->acceptJob($request->all(), $request->__authenticatedUser));
    }

    public function acceptJobWithId(Request $request)
    {
        return $this->repository->acceptJobWithId($request->get('job_id'),  $request->__authenticatedUser);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
       return response($this->repository->cancelJobAjax( $request->all(), $request->__authenticatedUser));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        return response($this->repository->endJob($request->all()));
    }

    public function customerNotCall(Request $request)
    {
        return response($this->repository->customerNotCall($request->all()));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {

        return response($this->repository->getPotentialJobs( $request->__authenticatedUser));

    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $jobId = $data['jobid'] ?? null;

        $distance = $data['distance'] ?? '';
        $time = $data['time'] ?? '';
        $session = $data['session_time'] ?? '';

        $flagged = $data['flagged'] === 'true' ? 'yes' : 'no';
        $manuallyHandled = $data['manually_handled'] === 'true' ? 'yes' : 'no';
        $byAdmin = $data['by_admin'] === 'true' ? 'yes' : 'no';
        $adminComment = $data['admincomment'] ?? '';

        $affectedRows = 0;
        $affectedRows1 = 0;

        if ($jobId) {
            if ($distance || $time) {
                $affectedRows = Distance::where('job_id', $jobId)->update(['distance' => $distance, 'time' => $time]);
            }

            if ($adminComment || $session || $flagged || $manuallyHandled || $byAdmin) {
                $jobData = [
                    'admin_comments' => $adminComment,
                    'flagged' => $flagged,
                    'session_time' => $session,
                    'manually_handled' => $manuallyHandled,
                    'by_admin' => $byAdmin,
                ];
                $affectedRows1 = Job::where('id', $jobId)->update($jobData);
            }
        }

        if ($affectedRows || $affectedRows1) {
            return response('Record updated!');
        } else {
            return response('No changes made.');
        }
    }


    public function reopen(Request $request)
    {
        return $this->repository->reopen($request->all());
    }

    public function resendNotifications(Request $request)
    {
        $job = $this->repository->find($request->all()['jobid']);
        $job_data = $this->repository->jobToData($this->repository->find($request->all()['jobid']));
        $this->repository->sendNotificationTranslator($job, $job_data, '*');
        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
       try {
            $this->repository->sendSMSNotificationToTranslator($this->repository->find($request->all()['jobid']));
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
